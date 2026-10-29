<?php

namespace App\Enums;

enum DocumentVersionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Superseded = 'superseded';
}
