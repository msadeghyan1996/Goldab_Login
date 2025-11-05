from django.urls import path
from . import views

urlpatterns = [
    path('auth/start/', views.start_auth, name='start_auth'),
    path('auth/verify-otp/', views.verify_otp, name='verify_otp'),
    path('auth/login/', views.login_with_password, name='login_with_password'),
]