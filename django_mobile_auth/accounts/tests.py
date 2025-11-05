from django.test import TestCase
from django.urls import reverse
from .models import User, OTP
from django.utils import timezone
import datetime
from django.contrib.auth.hashers import make_password

class OTPFlowTest(TestCase):
    def test_start_creates_otp_for_new_user(self):
        resp = self.client.post(reverse('start_auth'), data={'phone': '+100000'}, content_type='application/json')
        self.assertEqual(resp.status_code, 200)
        self.assertTrue(OTP.objects.filter(phone='+100000').exists())

    def test_verify_and_create_user(self):
        phone = '09140000000'
        otp_plain = '123456'
        hashed = make_password(otp_plain)
        expires = timezone.now() + datetime.timedelta(minutes=5)
        OTP.objects.create(phone=phone, code_hash=hashed, expires_at=expires)
        resp = self.client.post(reverse('verify_otp'), data={
            'phone': phone, 'otp': otp_plain, 'fullname': 'User', 'national_code': '123456789'
        }, content_type='application/json')
        self.assertEqual(resp.status_code, 200)
        self.assertIn('token', resp.json())
        self.assertTrue(User.objects.filter(phone=phone).exists())

    def test_login_with_password(self):
        u = User.objects.create_user(phone='09140000000', password='pass123')
        resp = self.client.post(reverse('login_with_password'), data={'phone': u.phone, 'password': 'pass123'}, content_type='application/json')
        self.assertEqual(resp.status_code, 200)
        self.assertIn('token', resp.json())
