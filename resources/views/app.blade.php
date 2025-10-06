<!DOCTYPE html>
<html lang="id"
    @if (Request::is('auth/*')) class="dark-style customizer-hide" data-theme="theme-default"
   @else   data-theme="dark" @endif>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon/' . $website->header->favicon) }}">

    <meta name="description" content="{!! $website->header->description !!}">
    <meta name="keyword" content="{{ $website->header->keywords }}">
    <meta name="author" content="@akiracode">
    <meta name="theme-color" content="{{ $website->header->theme_color }}" />
    <meta name="robots" content="index, follow">
    <meta content="desktop" name="device">
    <meta name="coverage" content="Worldwide">
    <meta name="apple-mobile-web-app-title" content="{{ $website->header->apple_mobile_web_app_title }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="mobile-web-app-capable" content="yes">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="{{ $website->header->og->title }}">
    <meta property="og:site_name" content="{{ $website->header->og->site_name }}">
    <meta property="og:description" content="{!! $website->header->og->description !!}">
    <meta property="og:image" content="{{ asset('assets/images/icon/' . $website->header->og->image->url) }}">
    <meta property="og:image:alt" content="{{ $website->header->og->image->alt }}">

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --bg-body: #f6f8fd;
            --bg-dark: #0e1221;
            --primary-color: {{ $website->header->theme_color }};
            --secondary-color: #0e1221;
            --light-yellow: #deffea;
            --light-blue: #252f45e6;
            --dark-blue: #0e1221;
            --black: #34364a;
            --black-text: #6b7c8e;
            --dark-grey: #e5e9f2;
            --navbar: #14192be3;
            --light-grey: #f6f8fd;
            --white: #fff;
        }

        .desc-heading-name,
        h4.desc-heading_foot,
        span.desc-heading-name,
        span.time,
        span.poweredby {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .pull-left {
            float: left
        }

        .desc-heading_foot {
            position: relative;
            margin-bottom: 0;
            margin-top: 6px;
            top: 6px
        }

        .desc-heading-name {
            display: inline-block !important;
            overflow: hidden;
            float: left;
            width: 100%;
            color: #000
        }

        .desc-heading-name a {
            text-decoration: none
        }

        .desc-heading-name a span:first-child {
            color: #000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 45%
        }

        .desc-heading-name a span:nth-child(2) {
            color: #6c6c6cfa;
            font-size: 11px
        }

        h4.desc-heading_foot a {
            color: var (--cStoreBody);
            text-decoration: none
        }

        .a_new_display_toggle {
            display: none
        }

        .a_all_integration_information {
            margin-top: 15px;
            width: 75%;
            display: none
        }

        .a_font_small_chap {
            font-size: 12px
        }

        .a_cursor_pointer_a {
            cursor: pointer
        }

        .a_right_icon_not_complate {
            height: 25px;
            width: 25px;
            float: right;
            margin-right: 20px;
            color: red
        }



        .a_hover_text_not_complete {
            position: absolute;
            display: none;
            top: -20px;
            right: 50px
        }

        h4.desc-heading_foot {
            float: left;
            font-size: 12px;
            font-weight: 500;
            margin: -3px 0 0;
            width: 100%;
            overflow: hidden
        }

        .pull-left.icon {
            border-radius: 50%;
            overflow: hidden;
            width: 58px;
            height: 58px;
            margin: 4px;
            z-index: 10;
            position: relative
        }

        .alert-widget .icon img {
            width: 100%
        }

        .tsp-widget .desc.desc_live_preview {
            float: left;
            line-height: 17px;
            margin-top: 0;
            position: absolute;
            left: 0;
            top: 0;
            padding: 5px 9px 0 74px;
            width: 100%;
            max-width: 318px;
            height: 70px
        }

        .alert-widget {
            font-family: Poppins, sans-serif;
            border: 0;
            display: inline-block;
            padding: 0;
            margin: 0 auto 20px;
            background: 0 0;
            width: 318px;
            height: 70px !important;
            position: fixed;
            left: 80px;
            bottom: -20px
        }

        .alert-widget .icon {
            margin: -2px 15px -2px -2px
        }

        .alert-widget .icon img {
            max-width: 64px
        }

        .alert-widget .desc {
            float: r;
            position: relative
        }

        .alert-widget .desc-heading {
            line-height: 20px;
            margin-top: 8px;
            font-weight: 600;
            margin-bottom: 0;
            float: left;
            width: 100%;
            border: 0;
            display: block;
            font-size: 15px;
            font-family: Poppins, sans-serif !important
        }

        .alert-widget .desc-heading small {
            display: block;
            color: #000;
            position: relative;
            width: 100%;
            border: 0;
            font-size: inherit;
            text-align: left;
            margin: 0;
            top: 0;
            font-weight: 600
        }

        .alert-widget .desc .time {
            color: #909090;
            font-size: 11px;
            position: relative;
            margin-top: 10px;
            float: left;
            width: auto;
            font-weight: 300;
            max-width: 45%
        }

        .alert-widget-4 {
            border: 0;
            padding: 0;
            background: 0 0
        }

        .alert-widget-4 .desc {
            background: #fff;
            padding: 8px 30px 5px;
            border-radius: 50px
        }

        .alert-widget-4 .icon {
            margin: 6px !important
        }

        .alert-widget-4 .icon img {
            max-width: 70px;
            border-radius: 50%;
            padding: 0;
            top: 0
        }

        .alert-widget-4 span.poweredby {
            margin-right: 15px
        }

        .wizard-widgetList.right {
            width: 360px
        }

        span.desc-heading-name {
            display: inline-block !important;
            overflow: hidden;
            float: left;
            width: 100%;
            font-family: Poppins, sans-serif;
            font-size: 12px;
            line-height: 1
        }

        .desc.desc_live_preview {
            float: left;
            line-height: 17px;
            margin-top: 0;
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            padding: 5px 9px 0 74px;
            width: 100%;
            background: #fff
        }

        .wizard-widgetList li {
            padding: 0 0 0 20px
        }

        .tsp-widget span.poweredby {
            float: right;
            margin-top: 10px;
            font-size: 11px;
            text-decoration: none;
            color: #9c9c9c;
            max-width: 45%
        }

        .tsp-widget span.poweredby i {
            color: #3498db;
            margin-right: 3px
        }

        .tsp-widget span.poweredby a {
            font-weight: 600;
            text-decoration: none;
            margin-left: 3px;
            color: #3498db
        }

        .tsp-widget span.poweredby b {
            color: #222
        }

        .tsp-widget span.poweredby a:hover {
            text-decoration: underline
        }

        .tsp-widget img.live_preview_image {
            background: #fff;
            width: 90%;
            height: 90%
        }

        .tsp-widget div.tsp-has-close-button {
            position: absolute;
            top: 0;
            right: 0;
            color: #000;
            text-shadow: 1px 1px #ddd;
            cursor: pointer;
            margin-right: 10px !important;
            margin-top: 8px !important;
            cursor: pointer;
            content: "\00d7"
        }

        @media screen and (max-width: 568px) {
            .tsp-widget.alert-widget {
                bottom: 20px !important;
                left: 15px !important;
                margin: 0 !important;
                position: fixed !important;
                width: 100% !important;
                max-width: 320px !important
            }

            .tsp-widget.alert-widget .desc_live_preview {
                width: 100% !important;
                max-width: 100% !important
            }
        }

        @media screen and (max-width: 320px) {
            .tsp-widget.alert-widget {
                bottom: 0 !important;
                left: 0 !important;
                margin: 0 !important;
                position: fixed !important;
                width: 100% !important;
                max-width: 320px !important
            }

            .tsp-widget.alert-widget .desc_live_preview {
                width: 100% !important;
                max-width: 100% !important
            }
        }

        .tidio-135wcf7 svg {
            width: 50px !important;
            display: none;
        }

        .rotate-icon svg {
            transition: transform 0.3s ease-in-out;
        }

        .rotate-icon:hover svg {
            transform: rotate(90deg);
            /* Atur sudut rotasi yang diinginkan */
        }
    </style>

    @vite('resources/js/app.js')
    @inertiaHead
</head>

<body class="antialiased">
    @routes
    @inertia
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
</body>

</html>
