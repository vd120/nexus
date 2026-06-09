<?php

return [
    // Welcome Email
    'welcome_subject' => '🎉 Welcome to :app_name - Let\'s Get Started!',
    'welcome_title' => 'Welcome!',
    'welcome_subtitle' => 'We\'re excited to have you on board',
    'welcome_to_nexus' => 'Welcome to Nexus!',
    'welcome_message' => 'Your journey to discover stories, connect with others, and explore AI-powered features starts here.',
    'hello' => 'Hello',
    'getting_started_message' => 'To get the most out of your experience, please verify your email address by clicking the button below.',

    'verify_email_button' => 'Verify My Email',
    'verify_email_note' => 'This link will expire in 24 hours',
    'what_you_can_do' => 'What You Can Do on Nexus',
    'feature_stories_title' => 'Read & Share Stories',
    'feature_stories_desc' => 'Discover amazing stories from our community or share your own with the world.',
    'feature_chat_title' => 'Real-time Chat',
    'feature_chat_desc' => 'Connect with other users through instant messaging and group conversations.',
    'feature_ai_title' => 'AI Assistant',
    'feature_ai_desc' => 'Get help, answers, and creative assistance from our advanced AI companion.',
    'feature_community_title' => 'Join the Community',
    'feature_community_desc' => 'Follow users, build your network, and be part of something bigger.',
    'security_note_title' => 'Security Tip',
    'security_note_text' => 'Never share your password with anyone. Our team will never ask for your password.',
    'footer_welcome_text' => 'This email was sent to welcome you to Nexus.',
    'footer_need_help' => 'Need help?',
    'footer_contact_us' => 'Contact us',

    // Login Notification (simpler, friendly)
    'login_notification_subject' => '✅ Login Notification - :app_name',
    'login_notification_title' => 'Login Successful',
    'login_notification_subtitle' => 'A new login was detected on your account',
    
    // Message
    'login_notification_message' => 'We wanted to let you know that a new login was detected on your account. Here are the details:',
    
    // Sections
    'login_details' => 'Login Details',
    'location' => 'Location',
    'coordinates' => 'Coordinates',
    'ip_address' => 'IP Address',
    'device_type' => 'Device Type',
    'browser' => 'Browser',
    'operating_system' => 'Operating System',
    'login_time' => 'Login Time',
    'timezone' => 'Timezone',
    'isp' => 'ISP',
    'unknown' => 'Unknown',
    
    // Call to Action
    'view_activity_logs' => 'View All Activity',
    
    // Footer
    'footer_notification_text' => 'This email was sent to notify you about login activity on your account.',
    'footer_ignore' => 'If this was you, you can safely ignore this email.',
    
    // Suspicious Login Alert
    'suspicious_login_alert' => 'We detected unusual login activity. Please review the details below and secure your account if this wasn\'t you.',
    'security_recommendations' => 'Security Recommendations',
    'security_tip_change_password' => 'Change your password immediately if this wasn\'t you',
    'security_tip_enable_2fa' => 'Enable two-factor authentication for extra security',
    'security_tip_review_sessions' => 'Review your active sessions and log out from unrecognized devices',
    'security_tip_never_share_password' => 'Never share your password with anyone',

    // Verification Code Email
    'verification_code_subject'          => ':app — Verification Code',
    'verification_code_security_subject' => ':app — Security Verification Code',
    'verification_code_login_subject'    => ':app — Login Security Code',
    'verification_code_welcome'          => 'Welcome to :app!',
    'verification_code_prompt'           => 'Your verification code is:',
    'verification_code_notice'           => 'Please enter this code on the verification page to confirm your account.',
    'verification_code_expire'           => 'This code will expire in 10 minutes.',
    'all_rights_reserved'                => 'All rights reserved.',

    // Password Reset Email
    'password_reset_subject' => ':app — Password Reset Request',
    'password_reset_title'   => 'Reset Your Password',
    'password_reset_greeting' => 'Hello!',
    'password_reset_body'    => 'You are receiving this email because we received a password reset request for your account.',
    'password_reset_button'  => 'Reset Password',
    'password_reset_expire'  => 'This password reset link will expire in 60 minutes.',
    'password_reset_ignore'  => 'If you did not request a password reset, no further action is required.',

    // Login Security Alert Email
    'security_alert_subject' => ':app — Suspicious Login Detected',
    'security_alert_title'   => 'Security Alert: Suspicious Login',
    'security_alert_greeting' => 'Hello :name,',
    'security_alert_body'    => 'We detected a login attempt that looks suspicious compared to your usual activity. As a security measure, we have temporarily blocked this access until it is verified.',
    'security_alert_device'  => 'Device',
    'security_alert_browser' => 'Browser',
    'security_alert_ip'      => 'IP Address',
    'security_alert_location' => 'Location',
    'security_alert_time'    => 'Time',
    'security_alert_footer'  => 'If this was you, please complete the verification on the login page. If you did not attempt to login, we recommend changing your password immediately.',

    // Data Export Email
    'data_export_subject' => ':app — Your data export is ready',
    'data_export_title' => 'Your data export is ready',
    'data_export_greeting' => 'Hi :name,',
    'data_export_body' => 'Your data export from :app is ready to download. The link will expire on :expires.',
    'data_export_button' => 'Download my data',
    'data_export_footer' => 'If you did not request this export, you can ignore this email. The link expires automatically after 48 hours.',
];
