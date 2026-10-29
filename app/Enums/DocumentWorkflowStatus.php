<?php

namespace App\Enums;

enum DocumentWorkflowStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
