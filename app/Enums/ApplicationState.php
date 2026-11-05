<?php

namespace App\Enums;

enum ApplicationState: string
{
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';

    // The states this one is allowed to move to next.
    public function nextStates(): array
    {
        return match ($this) {
            self::Submitted => [self::InReview],
            self::InReview => [self::Returned, self::Approved, self::Rejected],
            self::Returned => [self::Submitted],
            self::Approved => [self::Published],
            self::Rejected, self::Published => [],
        };
    }

    // True when the move from this state to $next is allowed.
    public function canMoveTo(self $next): bool
    {
        return in_array($next, $this->nextStates(), true);
    }

    // Rejected and Published have nowhere left to go.
    public function isFinal(): bool
    {
        return $this->nextStates() === [];
    }

    // Short name for the review screens (Japanese labels come later, in lang/ja).
    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::InReview => 'In review',
            self::Returned => 'Sent back',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Published => 'Published',
        };
    }
}
