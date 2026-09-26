<?php

namespace VanDmade\Cuztomisable\Enums\Organizations;

enum Role: string
{
    case Owner = 'owner';
    case Moderator = 'moderator';
    case Member = 'member';
}
