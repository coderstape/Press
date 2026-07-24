<?php

namespace coderstape\Press\Http\Middleware;

use Closure;
use coderstape\Press\Facades\Press;

class EnsureUserIsEditor
{
    /**
     * Reject any request from a signed-in user who is not on the
     * editor list.
     *
     * Deliberately does NOT duplicate what 'auth' does, and is meant
     * to run after it: 'auth' decides whether you are signed in and
     * sends guests to login, this decides whether being signed in is
     * enough. A signed-in non-editor gets a flat 403 rather than a
     * login redirect they could never satisfy by logging in again.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        abort_unless(Press::isEditor(), 403);

        return $next($request);
    }
}
