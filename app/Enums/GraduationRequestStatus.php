<?php

namespace App\Enums;

enum GraduationRequestStatus: string
{
      /** GraduationRequest is pending*/
    case PENDING = 'pending';
      /** GraduationRequest is approved */
    case APPROVED = 'approved';
      /** GraduationRequest is rejected  */
    case REJECTED = 'rejected';
}
