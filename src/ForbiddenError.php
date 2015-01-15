<?php

namespace Crell\Stacker;


class ForbiddenError extends HttpError
{
    public function defaultMessage()
    {
        return "Forbidden";
    }
}
