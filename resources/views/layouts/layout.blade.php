<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title')</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-PD8TXBM');</script>
    <!-- End Google Tag Manager -->
    @stack('pageSpecificStyles')
</head>

<body
    @unless(empty($body_class))
        class="{{$body_class}}"
    @endunless
>

    @if (Auth::guest())
        <input type="hidden" id="isLoggedIn" name="isLoggedIn" value="0">
    @else
        <input type="hidden" id="isLoggedIn" name="isLoggedIn" value="1">
    @endif


    <div class="main">
        @component('components.navbar')
        @endcomponent

        <div class="main_content">
            @yield("middle_content")
        </div>

        @component('components.footer')
        @endcomponent
        <div>
        @component('components.signmodal')
        @endcomponent
    </div>
    <div>
        @component('components.authmodal')
        @endcomponent
    </div>


    </div>



    <script src="{{ mix('js/manifest.js')}}"></script>
    <script src="{{ mix('js/vendor.js')}}"></script>
    <script src="{{ mix('js/app.js')}}"></script>
    @stack('pageSpecificScripts')
    <!-- Facebook Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window,document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '706582599846681');
    fbq('track', 'PageView');
    </script>
    <noscript>
    <img height="1" width="1"
    src="https://www.facebook.com/tr?id=706582599846681&ev=PageView(44 B)
    https://www.facebook.com/tr?id=706582599846681&ev=PageView

    &noscript=1"/>
    </noscript>
    <!-- End Facebook Pixel Code -->
    <script type="text/javascript">
        var vglnk = {key: '7c7cd49fe471830c75c9967f05d5f292'};
        (function(d, t) {
            var s = d.createElement(t);
                s.type = 'text/javascript';
                s.async = true;
                s.src = '//cdn.viglink.com/api/vglnk.js';
            var r = d.getElementsByTagName(t)[0];
                r.parentNode.insertBefore(s, r);
        }(document, 'script'));
    </script>


    <!-- Authentication Links -->
    @if (Auth::guest())
    <script>
        window.intercomSettings = {
            app_id: "es5imqpc"
        };
    </script>

    <script>(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/es5imqpc';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();</script>

    @else

    <script>
        window.intercomSettings = {
            app_id: "es5imqpc",
            name: {{ Auth::user()->name }}, // Full name
            email: {{ Auth::user()->email }}, // Email address
            created_at: {{ strtotime(Auth::user()->created_at) }} // Signup date as a Unix timestamp
        };
    </script>

    <script>(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/es5imqpc';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();</script>
    @endif

    <script>
        window['_fs_debug'] = false;
        window['_fs_host'] = 'fullstory.com';
        window['_fs_script'] = 'fullstory.com/s/fs.js';
        window['_fs_org'] = 'P2QG6';
        window['_fs_namespace'] = 'FS';
        (function(m,n,e,t,l,o,g,y){
        if (e in m) {if(m.console && m.console.log) { m.console.log('FullStory namespace conflict. Please set window["_fs_namespace"].');} return;}
        g=m[e]=function(a,b,s){g.q?g.q.push([a,b,s]):g._api(a,b,s);};g.q=[];
        o=n.createElement(t);o.async=1;o.crossOrigin='anonymous';o.src='https://'+_fs_script;
        y=n.getElementsByTagName(t)[0];y.parentNode.insertBefore(o,y);
        g.identify=function(i,v,s){g(l,{uid:i},s);if(v)g(l,v,s)};g.setUserVars=function(v,s){g(l,v,s)};g.event=function(i,v,s){g('event',{n:i,p:v},s)};
        g.shutdown=function(){g("rec",!1)};g.restart=function(){g("rec",!0)};
        g.log = function(a,b) { g("log", [a,b]) };
        g.consent=function(a){g("consent",!arguments.length||a)};
        g.identifyAccount=function(i,v){o='account';v=v||{};v.acctId=i;g(o,v)};
        g.clearUserCookie=function(){};
        })(window,document,window['_fs_namespace'],'script','user');
    </script>
</body>

</html>
