<?php

namespace Plugins\Pages\Enums;

enum PagesPermission: string
{
    case ViewPages = 'pages.view_pages';
    case CreatePages = 'pages.create_pages';
    case EditPages = 'pages.edit_pages';
    case PublishPages = 'pages.publish_pages';
    case DeletePages = 'pages.delete_pages';
}
