from rest_framework import serializers
from .models import User


class StartSerializer(serializers.Serializer):
    phone = serializers.CharField()


class LoginSerializer(serializers.Serializer):
    phone = serializers.CharField()
    password = serializers.CharField()


class VerifyOTPSerializer(serializers.Serializer):
    phone = serializers.CharField()
    otp = serializers.CharField()
    fullname = serializers.CharField(required=False, allow_blank=True)
    national_code = serializers.CharField(required=False, allow_blank=True)


class UserSerializer(serializers.ModelSerializer):
    class Meta:
        model = User
        fields = ['phone', 'fullname', 'national_code']
