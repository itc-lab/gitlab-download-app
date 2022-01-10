# Deployment

The following steps are for deploying OpenResty and PHP on a server with Omnibus GitLab installed.  
You can also use Apache as your web server or install it on another server.  
<br />
This procedure assumes the following:

- Deploy on Ubuntu 20.04.2 LTS.
- GitLab Omnibus is already installed on your local server.
- Use OpenResty instead of the GitLab's built-in Nginx.
- Use lua-resty-openidc as the OpenID Connect Relying Party (RP).
- Both GitLab and GitLab-Download-App use self-signed certificates.
- The URLs are as follows.  
  GitLab: `https://gitlab.itccorporation.jp`  
  GitLab-Download-App: `https://gitlab-download-app.itccorporation.jp`  
  Please replace it according to the actual environment.

## Introducing GitLab as OpenID Connect identity provider

### GitLab - http:// -> https://

```
# openssl genrsa -out ca.key 2048
# openssl req -new -key ca.key -out ca.csr
Country Name (2 letter code) [AU]:JP
State or Province Name (full name) [Some-State]:Aichi
Locality Name (eg, city) []:Toyota
Organization Name (eg, company) [Internet Widgits Pty Ltd]:
Organizational Unit Name (eg, section) []:
Common Name (e.g. server FQDN or YOUR name) []:gitlab.itccorporation.jp
Email Address []:

Please enter the following 'extra' attributes
to be sent with your certificate request
A challenge password []:
An optional company name []:
# echo "subjectAltName=DNS:*.itccorporation.jp,IP:192.168.12.111" > san.txt
# openssl x509 -req -days 365 -in ca.csr -signkey ca.key -out ca.crt -extfile san.txt
Signature ok
subject=C = JP, ST = Aichi, L = Toyota, O = Default Company Ltd, CN = test.itccorporation.jp
Getting Private key
# mkdir -p /etc/pki/tls/certs
# mkdir /etc/pki/tls/private
# cp ca.crt /etc/pki/tls/certs/ca.crt
# cp ca.key /etc/pki/tls/private/ca.key
# cp ca.csr /etc/pki/tls/private/ca.csr
# vi /etc/gitlab/gitlab.rb
```

```
external_url 'http://gitlab.itccorporation.jp'
```

&#8595;

```
external_url 'https://gitlab.itccorporation.jp'
```

```
nginx['ssl_certificate'] = "/etc/pki/tls/certs/ca.crt"
nginx['ssl_certificate_key'] = "/etc/pki/tls/private/ca.key"
```

```
# gitlab-ctl reconfigure
# gitlab-ctl restart
```

### GitLab - add a new application

Register the Relying Party (RP).  
<br />
`Admin Area -> Applications -> New application`
<br />
**Name:** GitLab-Download-App  
**Redirect URI:** `https://gitlab-download-app.itccorporation.jp/oidc_callback`  
**Trusted:** ✓  
**Confidential:** ✓  
**Scopes:** read_api, openid, email  
| ![add a new application](https://user-images.githubusercontent.com/76575923/148752302-ed213e95-9ae4-4748-a0f3-56a9f356d14d.png) |
| :-------------------------------------------------------------------------------------------------------------------: |

Make a note of the Application ID, Secret, and Callback URL as you will need them later.

## OpenResty installation

Create SSO web application environment with OpenResty - lua-resty-openidc - PHP.  
If you don't deploy GitLab and GitLab-Download-App on the same server, you can do it on different servers.

```
# apt update
# apt -y install --no-install-recommends wget gnupg ca-certificates
# wget -O - https://openresty.org/package/pubkey.gpg | sudo apt-key add -
# echo "deb http://openresty.org/package/ubuntu $(lsb_release -sc) main" > openresty.list
# cp openresty.list /etc/apt/sources.list.d/
# apt update
# apt -y install --no-install-recommends openresty
# apt -y install openresty-opm
# opm install zmartzone/lua-resty-openidc
```

## PHP8 installation

```
# add-apt-repository ppa:ondrej/php
# apt update
# apt -y install php8.0 php8.0-gd php8.0-mbstring php8.0-common php8.0-curl
# apt -y remove apache2-*
# apt install -y php-fpm
# vi /usr/local/openresty/nginx/conf/fastcgi_params
```

```
fastcgi_param  SCRIPT_NAME        $fastcgi_script_name;
```

&#8595;

```
fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;
fastcgi_param  SCRIPT_NAME        $fastcgi_script_name;
```

```
# unzip gitlab-download-app-main.zip
# mkdir -p /opt/gitlab-download-app/www/html
# chown -R nobody:nogroup /opt/gitlab-download-app
# mkdir /var/log/gitlab-download-app
# vi /etc/php/8.0/fpm/pool.d/www.conf
```

```
user = nobody
group = nogroup
...(omitted)...
listen.owner = nobody
listen.group = nogroup
```

```
# openssl genrsa -out ca2.key 2048
# openssl req -new -key ca2.key -out ca2.csr
Country Name (2 letter code) [AU]:JP
State or Province Name (full name) [Some-State]:Aichi
Locality Name (eg, city) []:Toyota
Organization Name (eg, company) [Internet Widgits Pty Ltd]:
Organizational Unit Name (eg, section) []:
Common Name (e.g. server FQDN or YOUR name) []:gitlab-download-app.itccorporation.jp
Email Address []:

Please enter the following 'extra' attributes
to be sent with your certificate request
A challenge password []:
An optional company name []:
# echo "subjectAltName=DNS:*.itccorporation.jp,IP:192.168.11.11" > san2.txt
# openssl x509 -req -days 365 -in ca2.csr -signkey ca2.key -out ca2.crt -extfile san2.txt
# cp ca2.crt /etc/pki/tls/certs/ca2.crt
# cp ca2.key /etc/pki/tls/private/ca2.key
# cp ca2.csr /etc/pki/tls/private/ca2.csr
# vi /usr/local/openresty/nginx/conf/gitlab-download-app.conf
```

```
resolver 127.0.0.53 ipv6=off;
lua_package_path '$prefixlua/?.lua;;';
lua_ssl_trusted_certificate /etc/pki/tls/certs/ca2.crt;

server
{
  listen 443 ssl;
  ssl_certificate /etc/pki/tls/certs/ca2.crt;
  ssl_certificate_key /etc/pki/tls/private/ca2.key;
  server_name gitlab-download-app.itccorporation.jp;
  access_log /var/log/gitlab-download-app/access.log;
  error_log /var/log/gitlab-download-app/error.log;

  root /opt/gitlab-download-app/www/html;
  index index.html index.htm index.php;
  access_by_lua_block {
    local opts = {
      ssl_verify = "no",
      scope = "openid email",
      session_contents = {id_token=true},
      use_pkce = true,
      redirect_uri = "https://gitlab-download-app.itccorporation.jp/oidc_callback",
      discovery = "https://gitlab.itccorporation.jp/.well-known/openid-configuration",
      client_id = "48b10a860b20a2eec4a7682bb88fd51fd786e78d6d84a0aabe4beafce9b50952",
      client_secret = "1aa56da3e07bbc7779406856023c4c5b34371bc64fef8d8a386aef64cc04eba1",
    }
    local res, err = require("resty.openidc").authenticate(opts)

    if err then
      ngx.status = 500
      ngx.say(err)
      ngx.exit(ngx.HTTP_INTERNAL_SERVER_ERROR)
    end

    ngx.req.set_header("X-USER", res.id_token.email)
  }

  location ~ [^/]\.php(/|$)
  {
    fastcgi_split_path_info ^(.+?\.php)(/.*)$;
    if (!-f $document_root$fastcgi_script_name)
    {
      return 404;
    }

    client_max_body_size 100m;

    # Mitigate https://httpoxy.org/ vulnerabilities
    fastcgi_param HTTP_PROXY "";

    # fastcgi_pass 127.0.0.1:9000;
    fastcgi_pass unix:/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;

    # include the fastcgi_param setting
    include fastcgi_params;

    # SCRIPT_FILENAME parameter is used for PHP FPM determining
    #  the script name. If it is not set in fastcgi_params file,
    # i.e. /etc/nginx/fastcgi_params or in the parent contexts,
    # please comment off following line:
    # fastcgi_param  SCRIPT_FILENAME   $document_root$fastcgi_script_name;
  }
}
```

`resolver 127.0.0.53 ipv6=off;`  
This setting is used for the environment where the name is resolved by /etc/hosts of Ubuntu 20.04.2 LTS.  
Generally, you specify the IP address of the DNS server.

`lua_ssl_trusted_certificate /etc/pki/tls/certs/ca2.crt;`  
This is the setting of the certificate storage for accessing https:// from Lua.

`scope`, `redirect_uri`, `client_id`, `client_secret`  
The value displayed when "GitLab - add a new application".

`ssl_verify = "no"`  
This is required if GitLab is a self-signed certificate site.

```
# vi /usr/local/openresty/nginx/conf/nginx.conf
```

```
...(omitted)...
    #    location / {
    #        root   html;
    #        index  index.html index.htm;
    #    }
    #}

include gitlab-download-app.conf;
}
```

```
# vi /etc/hosts
```

```
192.168.xxx.xxx gitlab.itccorporation.jp
192.168.xxx.xxx gitlab-download-app.itccorporation.jp
```

```
# systemctl restart openresty
# systemctl restart php8.0-fpm
```

## Change built-in Nginx to OpenResty

If you're deploying GitLab and GitLab-Download-App to separate servers, you don't need to follow the steps in this section.

```
# vi /etc/gitlab/gitlab.rb
```

```
# nginx['enable'] = true
```

&#8595;

```
nginx['enable'] = false
```

```
# gitlab-ctl stop nginx
# gitlab-ctl reconfigure
# vi /usr/local/openresty/nginx/conf/nginx.conf
```

```
user gitlab-www;
```

```
# vi /etc/php/8.0/fpm/pool.d/www.conf
```

```
user = gitlab-www
group = gitlab-www
...(omitted)...
listen.owner = gitlab-www
listen.group = gitlab-www
```

```
# chown -R gitlab-www: /opt/gitlab-download-app
# vi /usr/local/openresty/nginx/conf/gitlab-ssl.conf
```

You can find gitlab-ssl.conf [here](https://gist.github.com/itc-lab/df0db86386037795ca990d8ef18a4cc3).

Please modify `server_name`, `access_log`, `error_log`, `ssl_certificate`, `ssl_certificate_key` as necessary.

```
# vi /usr/local/openresty/nginx/conf/nginx.conf
```

```
...(omitted)...
    #}
include gitlab-ssl.conf;
include gitlab-download-app.conf;
}
```

```
# systemctl restart openresty
# systemctl restart php8.0-fpm
```
