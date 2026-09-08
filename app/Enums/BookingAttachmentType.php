<?php

namespace App\Enums;

enum BookingAttachmentType: string
{
    case Banner = 'banner';
    case Documentation = 'documentation';
    case MeetingMinutes = 'meeting_minutes';
}
