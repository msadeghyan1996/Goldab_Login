from django.db import models
from django.contrib.auth.models import AbstractBaseUser, PermissionsMixin, BaseUserManager
from django.core.validators import RegexValidator
from django.db.models.signals import pre_save
from django.utils import timezone
from django.utils.translation import gettext_lazy as _
from django.contrib.auth.hashers import make_password, check_password
from django.dispatch import receiver


class AccountManager(BaseUserManager):
    def _create_user(self, phone, password=None, **extra_fields):
        if not phone:
            raise ValueError(_("The given phone must be set!"))
        user = self.model(phone=phone, **extra_fields)
        user.set_password(password)
        user.save(using=self._db)
        return user

    def create_user(self, phone, **extra_fields):
        extra_fields.setdefault('is_staff', False)
        extra_fields.setdefault('is_superuser', False)
        extra_fields.setdefault('role', 'user')
        return self._create_user(phone, password=None, **extra_fields)

    def create_superuser(self, phone, password=None, **extra_fields):
        extra_fields.setdefault('is_staff', True)
        extra_fields.setdefault('is_superuser', True)
        extra_fields.setdefault('role', 'admin')

        if extra_fields.get('is_staff') is not True:
            raise ValueError('Superuser must have is_staff=True.')
        if extra_fields.get('is_superuser') is not True:
            raise ValueError('Superuser must have is_superuser=True.')

        return self._create_user(phone, password, **extra_fields)


class User(AbstractBaseUser, PermissionsMixin):
    uid = models.BigAutoField(primary_key=True)
    ROLE_CHOICES = (
        ('user', 'User'),
        ('admin', 'Admin'),
    )

    iran_phone_regex = RegexValidator(
        regex=r'^09\d{9}$',
        message="Phone number must be entered in the format: '09XXXXXXXXX'. Exactly 11 digits allowed."
    )

    phone = models.CharField(
        max_length=11,
        unique=True,
        validators=[iran_phone_regex],
        verbose_name='Phone Number'
    )
    fullname = models.CharField(max_length=150, verbose_name='FullName', null=True, blank=True)
    national_code = models.CharField(max_length=10, null=True, verbose_name="National code")

    is_staff = models.BooleanField(default=False, verbose_name='Staff Status')
    is_deleted = models.BooleanField(default=False, verbose_name='Deleted Status')
    date_joined = models.DateTimeField(auto_now=True)

    role = models.CharField(
        max_length=10,
        choices=ROLE_CHOICES,
        default='user',
        verbose_name='User Role'
    )

    objects = AccountManager()

    USERNAME_FIELD = 'phone'
    REQUIRED_FIELDS = []


    class Meta:
        verbose_name = 'user'
        verbose_name_plural = 'users'

    def __str__(self):
        return f"{self.fullname}|{self.phone}"


@receiver(pre_save, sender=User)
def set_custom_user_id(sender, instance, **kwargs):
    if instance.uid is None:
        last_instance = User.objects.all().order_by('uid').last()
        if last_instance:
            instance.uid = last_instance.uid + 1
        else:
            instance.uid = 100


class OTP(models.Model):
    phone = models.CharField(max_length=32, db_index=True)
    code_hash = models.CharField(max_length=128)
    created_at = models.DateTimeField(auto_now_add=True)
    expires_at = models.DateTimeField()
    verified = models.BooleanField(default=False)

    def is_expired(self):
        return timezone.now() > self.expires_at