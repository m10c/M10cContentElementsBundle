<?php

declare(strict_types=1);

namespace M10c\ContentElements\Filter;

enum PublishableResolvedValue
{
    /** No filtering - all variants returned regardless of publishAt (e.g. admin endpoints). */
    case Any;

    /** publishAt IS NOT NULL AND <= NOW() (default for public endpoints). */
    case Published;

    /** publishAt IS NOT NULL (includes future-scheduled variants, e.g. for scheduled content previews). */
    case PublishedOrScheduled;
}
