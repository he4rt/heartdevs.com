<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        @yield('metatags')

        <title>Document</title>
        @vite('')
        @yield('styles')
    </head>
    <body>
        @yield('content', 'Not implemented')

        @yield('scripts')
    </body>
</html>
