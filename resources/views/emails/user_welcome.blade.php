<p>Hello {{ $user->name }},</p>

<p>Your account has been created. Please verify your email and use the credentials below to log in:</p>

<p><strong>Username:</strong> {{ $user->email }}<br>
<strong>Password:</strong> {{ $plainPassword }}</p>

<p><a href="{{ $verificationUrl }}" style="background:#2563eb;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;">Verify Email</a></p>

<p>If you did not request this, please ignore this email.</p>

<p>Thank you,<br>CRM Team</p>
