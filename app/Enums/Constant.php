<?php

namespace App\Enums;

class Constant
{
    /**
     * 搜索关键词来源
     */
    const SEARCH_HISTORY_FROM_WX = 'wx';
    const SEARCH_HISTORY_FROM_APP = 'app';
    const SEARCH_HISTORY_FROM_PC = 'pc';

    /**
     * 收藏类型
     */
    const COLLECT_TYPE_GOODS = 0;
    const COLLECT_TYPE_TOPIC = 1;

    /**
     * 评价类型
     */
    const COMMENT_TYPE_GOODS = 0;
    const COMMENT_TYPE_TOPIC = 1;
}