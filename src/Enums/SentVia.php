<?php

namespace VanDmade\Cuztomisable\Enums;

/**
 * Delivery channel for MFA codes and password-reset codes: Email or Text.
 * Text's backing value stays 'phone', not 'text' - that's what SendRequest's `type` validation,
 * ForgotRequest, the frontend, and already-stored sent_via rows all use on the wire. Only the
 * PHP-side case name changed to Text; the value on the wire/in the database did not.
 */
enum SentVia: string
{
    case Email = 'email';
    case Text = 'phone';
}
