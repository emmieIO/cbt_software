<?php

namespace App\Enums;

enum Branch: string
{
    case NURSERY_VGC = 'nursery_vgc';
    case PRIMARY_VGC = 'primary_vgc';
    case HIGH_SCHOOL_VGC = 'high_school_vgc';
    case SCHOOL_ABUJA = 'school_abuja';
    case HIGH_SCHOOL_ABUJA = 'high_school_abuja';
    case SCHOOL_FESTAC = 'school_festac';
    case HIGH_SCHOOL_FESTAC = 'high_school_festac';
    case SCHOOL_LADIPO = 'school_ladipo';
    case SCHOOL_LEKKI = 'school_lekki';
    case HIGH_SCHOOL_LEKKI = 'high_school_lekki';
    case SCHOOL_OPEBI = 'school_opebi';
    case HIGH_SCHOOL_IKEJA = 'high_school_ikeja';
    case COLLEGE_IDIMU = 'college_idimu';
    case PRE_DEGREE_LEKKI = 'pre_degree_lekki';
    case COLLEGE_KATAMPE = 'college_katampe';
    case SCHOOL_KATAMPE = 'school_katampe';
}
