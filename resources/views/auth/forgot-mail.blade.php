<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('setting.company_name') }} - Password Reset OTP</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f7f7f7; padding: 20px;">
    <div
        style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #5640B0 0%, #7B68C4 100%); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Password Reset Request</h1>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <h2 style="color: #333; margin-top: 0;">Dear {{ $user['name'] }},</h2>

            <p style="font-size: 16px; color: #555;">We received a request to reset your password. Please use the OTP
                below to verify your identity and reset your password:</p>

            <!-- OTP Box -->
            <div
                style="background-color: #f0f0f0; border-left: 4px solid #5640B0; padding: 20px; margin: 30px 0; text-align: center;">
                <p style="margin: 0; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 1px;">Your
                    OTP Code</p>
                <h1
                    style="margin: 10px 0 0 0; font-size: 36px; color: #5640B0; letter-spacing: 8px; font-weight: bold;">
                    {{ $user['otp'] }}</h1>
            </div>

            <p style="font-size: 14px; color: #888; margin-top: 20px;">
                <strong>⏱️ This OTP will expire in 10 minutes.</strong>
            </p>

            <p style="font-size: 16px; color: #555; margin-top: 30px;">If you didn't request a password reset, please
                ignore this email or contact our support team if you have concerns.</p>

            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #e0e0e0;">
                <p style="font-size: 14px; color: #666; margin-bottom: 10px;">Need help? Contact our support team:</p>
                <p style="margin: 5px 0;"><strong>Email:</strong> <a
                        href="mailto:{{ config('setting.company_email', '') }}"
                        style="color: #5640B0; text-decoration: none;">{{ config('setting.company_email') }}</a></p>
                <p style="margin: 5px 0;"><strong>Phone:</strong> {{ config('setting.company_contact', '') }}</p>
            </div>

            <p style="margin-top: 30px; font-size: 16px; color: #333;">Best regards,</p>
            <p style="margin: 5px 0; font-size: 16px; color: #5640B0; font-weight: bold;">
                {{ config('setting.company_name') }} Team</p>
        </div>

        <!-- Footer -->
        <div style="background-color: #f7f7f7; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0; font-size: 12px; color: #999;">© {{ date('Y') }}
                {{ config('setting.company_name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
