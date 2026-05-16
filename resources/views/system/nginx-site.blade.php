server {
    listen 80;
    listen [::]:80;
    server_name {{ $domain }}@foreach ($aliases as $alias) {{ $alias }}@endforeach;

    root {{ $documentRoot }}/public;
    index index.php index.html;

    access_log /var/log/nginx/{{ $siteName }}.access.log;
    error_log  /var/log/nginx/{{ $siteName }}.error.log warn;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass {{ $phpFpmSocket }};
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
