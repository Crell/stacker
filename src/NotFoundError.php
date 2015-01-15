<?php

namespace Crell\Stacker;


class NotFoundError extends HttpError
{
    public function defaultMessage()
    {
        return "Not Found";
    }
}
