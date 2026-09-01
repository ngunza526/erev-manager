<?php

namespace App\Enums;

enum MemberStatus: string
{
    case Sympathisant = 'sympathisant';
    case Adherant = 'adherant';
    case Actif = 'actif';
    case Effectif = 'effectif';
}
