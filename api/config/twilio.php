<?php

function twilioConfig(): array {
    return [
        'enabled' => true,
        'account_sid' => getenv('TWILIO_ACCOUNT_SID'),
        'auth_token' => getenv('TWILIO_AUTH_TOKEN'),
        'messaging_service_sid' => getenv('TWILIO_MESSAGING_SERVICE_SID') ,
        'default_country_code' => getenv('TWILIO_DEFAULT_COUNTRY_CODE'),
        'send_on_public_request' => true,
        'send_on_internal_create' => true,
        'send_on_update' => true,
        'send_on_status_change' => true,
    ];
}
