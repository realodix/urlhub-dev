<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Url;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show all user short URLs.
     *
     * @return \Illuminate\View\View
     */
    public function view()
    {
        return view('backend.dashboard', [
            'url'  => new Url,
            'user' => new User,
        ]);
    }

    /**
     * Show the long url edit page.
     *
     * @param mixed $key
     * @return \Illuminate\View\View
     */
    public function edit($key)
    {
        $url = Url::whereKeyword($key)->firstOrFail();

        $this->authorize('updateUrl', $url);

        return view('backend.edit', compact('url'));
    }

    /**
     * Update the long url that was previously set to the new long url.
     *
     * @param Request $request \Illuminate\Http\Request
     * @param mixed   $url
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, $url)
    {
        $url->long_url = $request->long_url;
        $url->title = $request->title;
        $url->save();

        return redirect()->route('dashboard')
            ->withFlashSuccess(__('Link changed successfully !'));
    }

    /**
     * Delete a shortened URL on user request.
     *
     * @param mixed $url
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete($url)
    {
        $this->authorize('forceDelete', $url);

        $url->delete();

        return redirect()->back()
            ->withFlashSuccess(__('Link was successfully deleted.'));
    }

    /**
     * UrlHub only allows users (registered & unregistered) to have a unique
     * link. You can duplicate it and it will generated a new unique random
     * key.
     *
     * @param mixed $key
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate($key)
    {
        $url = new Url;
        $url->duplicate($key, Auth::id());

        return redirect()->back()
            ->withFlashSuccess(__('Link was successfully duplicated.'));
    }
}