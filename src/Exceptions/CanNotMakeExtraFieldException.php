<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Exceptions;

class CanNotMakeExtraFieldException extends \Exception
{
    public function __construct(string $className = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("The $className must be a instance of CanMakeExtraFieldInterface", $code, $previous);
    }
}
