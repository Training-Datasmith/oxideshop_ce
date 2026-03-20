<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database\Logger;

use function implode;
use function sprintf;
readonly class Query_Log_Filter implements Query_Log_Filter_Interface
{
    private const SHOULD_LOG_IF_CONTAINS_PATTERN = '(.?)(insert into|update |delete )';
    private const SHOULD_NOT_LOG_IF_CONTAINS_PATTERN = '(?!.*oxsession)(?!.*oxcache)';
    public function __construct(private array $skip_log_tags)
    {
    }
    public function should_log_query(string $query): bool
    {
        $additional_pattern_to_skip_logging = !empty($this->skip_log_tags) ? sprintf('(?!.*%s)', implode(')(?!.*', $this->skip_log_tags)) : '';
        return (bool) preg_match(sprintf('/%s%s%s/i', self::SHOULD_LOG_IF_CONTAINS_PATTERN, self::SHOULD_NOT_LOG_IF_CONTAINS_PATTERN, $additional_pattern_to_skip_logging), $query);
    }
}