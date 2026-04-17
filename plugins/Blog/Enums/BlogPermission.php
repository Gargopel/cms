<?php

namespace Plugins\Blog\Enums;

enum BlogPermission: string
{
    case ViewPosts = 'blog.view_posts';
    case CreatePosts = 'blog.create_posts';
    case EditPosts = 'blog.edit_posts';
    case PublishPosts = 'blog.publish_posts';
    case DeletePosts = 'blog.delete_posts';
    case ManageCategories = 'blog.manage_categories';
    case ManageTags = 'blog.manage_tags';
    case ManageSettings = 'blog.manage_settings';
}
