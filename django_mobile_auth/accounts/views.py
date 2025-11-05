import random, datetime, logging
from django.utils import timezone
from django.contrib.auth import authenticate
from django.contrib.auth.hashers import make_password, check_password
from rest_framework.decorators import api_view
from rest_framework.response import Response
from rest_framework import status
from rest_framework.authtoken.models import Token
from .models import User, OTP
from .serializers import StartSerializer, VerifyOTPSerializer, LoginSerializer

logger = logging.getLogger(__name__)
OTP_TTL_SECONDS = 5 * 60  # 5 minutes

def _generate_otp():
    return '{:06d}'.format(random.randint(0, 999999))

@api_view(['POST'])
def start_auth(request):
    serializer = StartSerializer(data=request.data)
    serializer.is_valid(raise_exception=True)
    phone = serializer.validated_data['phone']
    user_exists = User.objects.filter(phone=phone).exists()
    if user_exists:
        return Response({'exists': True})
    otp = _generate_otp()
    hashed = make_password(otp)
    expires = timezone.now() + datetime.timedelta(seconds=OTP_TTL_SECONDS)
    OTP.objects.create(phone=phone, code_hash=hashed, expires_at=expires)
    logger.warning(f'DEV OTP for {phone}: {otp} (expires {expires.isoformat()})')
    return Response({'otp_sent': True})

@api_view(['POST'])
def verify_otp(request):
    serializer = VerifyOTPSerializer(data=request.data)
    serializer.is_valid(raise_exception=True)
    phone = serializer.validated_data['phone']
    otp = serializer.validated_data['otp']
    fullname = serializer.validated_data.get('first_name', '')
    national_code = serializer.validated_data.get('national_id', '')

    candidate = OTP.objects.filter(phone=phone, verified=False).order_by('-created_at').first()
    if not candidate or candidate.is_expired():
        return Response({'detail': 'OTP not found or expired'}, status=status.HTTP_400_BAD_REQUEST)
    if not check_password(otp, candidate.code_hash):
        return Response({'detail': 'Invalid OTP'}, status=status.HTTP_400_BAD_REQUEST)

    candidate.verified = True
    candidate.save()

    user, created = User.objects.get_or_create(phone=phone, defaults={
        'fullname': fullname,
        'national_code': national_code,
    })

    if not created:
        updated = False
        if fullname and not user.fullname:
            user.fullname = fullname; updated = True
        if national_code and not user.national_code:
            user.national_code = national_code; updated = True
        if updated:
            user.save()

    token, _ = Token.objects.get_or_create(user=user)
    return Response({'token': token.key, 'created': created})

@api_view(['POST'])
def login_with_password(request):
    serializer = LoginSerializer(data=request.data)
    serializer.is_valid(raise_exception=True)
    phone = serializer.validated_data['phone']
    password = serializer.validated_data['password']
    user = authenticate(request, username=phone, password=password)
    if not user:
        return Response({'detail': 'Invalid credentials'}, status=status.HTTP_400_BAD_REQUEST)
    token, _ = Token.objects.get_or_create(user=user)
    return Response({'token': token.key})
