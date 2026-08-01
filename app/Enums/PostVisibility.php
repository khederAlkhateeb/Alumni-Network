<?php

namespace App\Enums;

/**
 * Enum PostVisibility
 *
 * Defines the privacy and visibility levels for a post, determining who is allowed to view it.
 *
 * @package App\Enums
 */
enum PostVisibility: string
{
    /** The post is visible to everyone. */
    case Public = 'public';

    /** The post is only visible to the user's direct connections. */
    case Connections = 'connections';

    /** The post is only visible to users within the same university network. */
    case University = 'university';
}
