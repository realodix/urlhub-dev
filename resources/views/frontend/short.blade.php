@extends('layouts.frontend')

@section('css_class', 'frontend view_short')

@section('content')
    <div class="max-w-7xl mx-auto mb-12">
        <div class="flex flex-wrap mt-6 lg:mt-8 px-4 sm:p-6">
            <div class="md:w-9/12">

                @include('partials/messages')

                <div class="text-xl sm:text-2xl lg:text-3xl font-bold mb-4">{!! $url->title !!}</div>

                <ul class="mb-4">
                    <li class="inline-block pr-4">
                        @svg('icon-calendar')
                        <i>{{$url->created_at->toDayDateTimeString()}}</i>
                    </li>
                    <li class="inline-block pr-4 mt-4 lg:mt-0">
                        @svg('icon-bar-chart')
                        <i><span title="{{number_format($url->click)}}">{{compactNumber($url->click)}}</span></i>
                        {{__('Total engagements')}}
                    </li>
                    @auth
                        @if (Auth::user()->hasRole('admin') || (Auth::user()->id === $url->user_id))
                            <li class="inline-block pr-2">
                                <a href="{{route('dashboard.su_edit', $url->keyword)}}" title="{{__('Edit')}}"
                                    class="btn-icon text-xs">
                                    @svg('icon-edit')
                                </a>
                            </li>
                            <li class="inline-block">
                                <a href="{{route('su_delete', $url->getRouteKey())}}" title="{{__('Delete')}}"
                                    class="btn-icon text-xs hover:text-red-700 active:text-red-600">
                                    @svg('icon-trash')
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>
            </div>
        </div>

        <div class="common-card-style flex flex-wrap mt-6 sm:mt-0 px-4 py-5 sm:p-6">
            @if (config('urlhub.qrcode'))
                <div class="w-full md:w-1/4 flex justify-center">
                    <img class="qrcode" src="{{$qrCode->getDataUri()}}" alt="QR Code">
                </div>
            @endif
            <div class="w-full md:w-3/4 mt-8 sm:mt-0">
                <button title="{{__('Copy the shortened URL to clipboard')}}"
                    data-clipboard-text="{{$url->short_url}}"
                    class="btn-clipboard btn-icon text-xs ml-4">
                    @svg('icon-clone') {{__('Copy')}}
                </button>

                <br>

                <span class="font-bold text-indigo-700 text-xl sm:text-2xl">
                    <a href="{{ $url->short_url }}" target="_blank" id="copy">
                        {{urlDisplay($url->short_url, scheme: false)}}
                    </a>
                </span>

                <div class="break-all max-w-2xl mt-2">
                    @svg('arrow-turn-right') <a href="{{ $url->long_url }}" target="_blank" rel="noopener noreferrer" class="redirect-anchor">{{ urlDisplay($url->long_url, limit: 80) }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
