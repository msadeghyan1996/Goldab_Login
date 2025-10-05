🧩 Project Overview
We need a login page that works as follows:

Prompt the user for their mobile number.

If the mobile number already exists, redirect them to the password form. After entering the password correctly, they will be taken to a page in the panel.

If the mobile number does not exist, redirect them to the OTP code page. After entering the SMS verification code correctly, redirect them to the user information page (first name & last name – national ID).

Once the information is completed, the user will be taken to a page in the panel.

✅ Project Requirements

Use a queue for generating OTP codes.

Use Redis as the queue driver.

Handle Persian/Arabic digits in form inputs.

Prevent excessive requests to the OTP code request form (rate limiting).

Proper use of Blade templates.

Tech stack: Laravel 12, MySQL, Redis

Important: Please do not treat this as a simple login screen. Approach it as if you’re building a large-scale, production-ready system with proper architecture, scalability, and security in mind.
