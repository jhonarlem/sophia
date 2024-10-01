<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    if (Auth::check() && Auth::user()->status === 'Inactivo') {
      Notification::make()
        ->title('Tu cuenta está desactivada.')
        // ->warning()
        ->icon('heroicon-o-shield-exclamation')
        ->color('danger')
        ->iconColor('danger')
        ->send();
      Auth::logout();
      return redirect()->route('filament.dashboard.auth.login')->withErrors(['status' => 'Tu cuenta está desactivada.']);
    }
    return $next($request);
  }
}
