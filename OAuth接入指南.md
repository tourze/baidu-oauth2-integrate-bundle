from https://openauth.baidu.com/doc/doc.html

## 1. 概述

如果百度用户访问第三方应用网页，则第三方应用可以通过网页授权机制，来获取百度用户基本信息，进而实现自身业务功能。 ![](https://openauth.baidu.com/doc/assets/img/authorize.1d71cfc8.png)

具体而言，百度帐号网页授权流程分为四步：

1.  引导用户进入授权页面同意授权，获取code；

2.  通过code换取网页授权access\_token；

3.  如果需要，开发者可以刷新网页授权access\_token，避免过期；

4.  通过网页授权access\_token获取用户基本信息。


## [#](https://openauth.baidu.com/doc/doc.html#_2-%E5%BC%95%E5%AF%BC%E7%94%A8%E6%88%B7%E5%AE%8C%E6%88%90%E6%8E%88%E6%9D%83%E8%8E%B7%E5%8F%96code) 2. 引导用户完成授权获取code

开发时，需要将用户浏览器重定向到如下URL地址。 

接口调用请求说明：

    GET https://openapi.baidu.com/oauth/2.0/authorize?response_type=CODE&client_id=API_KEY&redirect_uri=REDIRECT_URI&scope=SCOPE&state=STATE


参数说明：

**参数名**

**类型**

**是否必须**

**描述**

response\_type

string 

是 

固定为 code。

client\_id

string

是

注册应用时获得的API Key。

redirect\_uri

string

是

授权后要回调的URI，即接收Authorization Code的URI。如果用户在授权过程中取消授权，会回调该URI，并在URI末尾附上error=access\_denied参数。对于无Web Server的应用，其值可以是“oob”，此时用户同意授权后，授权服务会将Authorization Code直接显示在响应页面的页面中及页面title中。非“oob”值的redirect\_uri按照如下规则进行匹配：（1）如果开发者在“授权安全设置”中配置了“授权回调地址”，则redirect\_uri必须与“授权回调地址”中的某一个相匹配； （2）如果未配置“授权回调地址”，redirect\_uri所在域名必须与开发者注册应用时所提供的网站根域名列表或应用的站点地址（如果根域名列表没填写）的域名相匹配。**授权回调地址配置请参考附录Ⅰ-1**。

scope

string

否

以空格分隔的权限列表，若不传递此参数，代表请求用户的默认权限。可填basic或mobile。

display

string

否

登录和授权页面的展现样式，默认为“page”，**具体参数定义请参考附录Ⅰ-2**。

state

string

否

重定向后会带上state参数。建议开发者利用state参数来防止CSRF攻击。

force\_login

int

否

如传递“force\_login=1”，则加载登录页时强制用户输入用户名和口令，不会从cookie中读取百度用户的登陆状态。

confirm\_login

int

否

如传递“confirm\_login=1”且百度用户已处于登陆状态，会提示是否使用已当前登陆用户对应用授权。

login\_type

string

否

如传递“login\_type=sms”，授权页面会默认使用短信动态密码注册登陆方式。

qrext\_clientid

string

否

网盘扫码透传字段

bgurl

string

否

二维码登录方式的背景图片url链接，需要encode

qrcodeW

int

否

自定义二维码图片的宽度

qrcodeH

int

否

自定义二维码图片的高度

qrcode

int

否

如传递“qrcode=1”，登录授权页面将增加扫码登录入口；**注：扫码登录入口点击跳转至二维码页面，目前支持PC、TV、音箱、watch、kindle**

qrloginfrom

string

否

扫码登录被扫码端设备类型；目前传参仅支持：pc、tv、speakers、watch、kindle；注：speakers为音箱的标志；**说明：此配置仅支持display=tv时；**

userReg

int

否

如传递“qrcode=1”，扫码登录页配置“用户名登录”、“注册”入口；**说明：此配置仅支持display=tv时；**

appTip

string

否

扫码登录页二维码底部提示文案，中文文案需encodeURIComponent('提示文案')；**说明：此配置仅支持display=tv时；**

appName

string

否

扫码登录页二维码底部app文案配置，中文文案需encodeURIComponent('网盘App')；**说明：此配置仅支持display=tv时；**

下图为登录授权页面：

![](https://openauth.baidu.com/doc/assets/img/oauthpage.6f6e552b.png)

无scope权限或redirect\_uri不合法时，会展示错误页面，并提示出错原因，如下图示：

![](https://openauth.baidu.com/doc/assets/img/error1.0518bf45.png)

![](https://openauth.baidu.com/doc/assets/img/error2.478c6b02.png)

用户同意授权后：页面将跳转至redirect\_uri/?code=CODE&state=STATE。 

code说明：code作为换取access\_token的票据，每次用户授权带上的code将不一样，code只能使用一次，10分钟未被使用自动过期。

## [#](https://openauth.baidu.com/doc/doc.html#_3-code%E8%8E%B7%E5%8F%96%E6%8E%88%E6%9D%83access-token) 3. code获取授权access\_token

redirect\_uri指定的开发者服务器地址，在获取到授权code参数后，从服务端向百度开放平台发起如下HTTP请求，通过code换取网页授权access\_token。 

注意：access\_token长度保留256字符。 

接口调用请求说明：

    GET https://openapi.baidu.com/oauth/2.0/token?grant_type=authorization_code&code=CODE&client_id=AP I_KEY&client_secret=SECRET_KEY&redirect_uri=REDIRECT_URI    


参数说明：

**参数名 **

**类型 **

**是否必须 **

**描述 **

grant\_type 

string 

是 

固定为authorization\_code 

code 

string 

是 

用户授权后得到code 

client\_id 

string 

是 

应用的API Key

client\_secret 

string 

是 

应用的Secret Key

redirect\_uri 

string 

是 

**该值必须与获取Authorization  Code时传递的“redirect\_uri”保持一致。**

返回值说明：

**字段名 **

**类型 **

**描述 **

access\_token 

string 

获取到的网页授权接口调用凭证 

expires\_in 

int 

Access Token的有效期，以秒为单位

refresh\_token 

string 

用于刷新Access Token的Refresh Token，所有应用都会返回该参数\*\*（10年的有效期\*\*）

scope 

string 

Access Token最终的访问范围，即用户实际授予的权限列表（用户在授权页面时，有可能会取消掉某些请求的权限）

session\_key 

string 

基于http调用Open API时所需要的Session Key，其有效期与Access Token一致

session\_secret

string 

基于http调用Open  API时计算参数签名用的签名密钥

错误情况下：

**字段名 **

**类型 **

**描述 **

error 

string 

错误码，**关于错误码的详细信息请参考附录Ⅰ-3**

error\_description 

string 

错误描述信息，用来帮助理解和解决发生的错误

返回值示例：

    {  
         "access_token":  "1.a6b7dbd428f731035f771b8d15063f61.86400.1292922000-2346678-124328",  
         "expires_in":  86400,  
         "refresh_token":  "2.385d55f8615fdfd9edb7c4b5ebdc3e39.604800.1293440400-2346678-124328",               
         "scope":  "basic  email",  
         "session_key":  "ANXxSNjwQDugf8615OnqeikRMu2bKaXCdlLxn",  
         "session_secret":  "248APxvxjCZ0VEC43EYrvxqaK4oZExMB"  
    } 


出错时返回：

    {  
         "error":  "invalid_grant",  
         "error_description":  "Invalid  authorization  code:  ANXxSNjwQDugOnqeikRMu2bKaXCdlLxn"   
    } 


## [#](https://openauth.baidu.com/doc/doc.html#_4-%E6%8C%89%E9%9C%80%E5%88%B7%E6%96%B0access-token) 4. 按需刷新access\_token

当access\_token过期后，可以使用refresh\_token进行刷新。refresh\_token有效期为十年。 

接口调用请求说明：

    GET https://openapi.baidu.com/oauth/2.0/token?grant_type=refresh_token&refresh_token=REFRESH_TOKEN &client_id=API_KEY&client_secret=SECRET_KEY 


参数说明：

**参数名**

**类型**

**是否必须**

**描述**

grant\_type

string

是

固定为refresh\_token

refresh\_token

string

是

通过access\_token获取到的refresh\_token参数

client\_id

string

是

应用的API Key

client\_secret

string

是

应用的Secret Key

返回值说明：

**字段名 **

**类型 **

**描述 **

access\_token 

string 

获取到的网页授权接口调用凭证 

expires\_in 

int 

Access Token的有效期，以秒为单位

refresh\_token 

string 

用于刷新Access Token的Refresh Token，所有应用都会返回该参数（**10年的有效期**）

scope 

string 

Access Token最终的访问范围，即用户实际授予的权限列表（用户在授权页面时，有可能会取消掉某些请求的权限）

session\_key 

string 

基于http调用OpenAPI时所需要的Session Key，其有效期与 Access Token一致

session\_secret 

string 

基于http调用OpenAPI时计算参数签名用的签名密钥。

错误情况下：

字段名 

类型 

描述 

error 

string 

错误码，**关于错误码的详细信息请参考附录Ⅰ-3**

error\_description 

string 

错误描述信息，用来帮助理解和解决发生的错误 

返回值示例：

    {  
         "access_token":  "1.a6b7dbd428f731035f771b8d15063f61.86400.1292922000-2346678-124328",               
         "expires_in":  86400,  
         "refresh_token":  "2.af3d55f8615fdfd9edb7c4b5ebdc3e32.604800.1293440400-2346678-124328",               
         "scope":  "basic  email",  
         "session_key":  "ANXxSNjwQDugf8615OnqeikRMu2bKaXCdlLxn",  
         "session_secret":  "248APxvxjCZ0VEC43EYrvxqaK4oZExMB"  
    } 


出错时返回：

    {
         "error": "expired_token",
         "error_description": "refresh token has been used"
    }


## [#](https://openauth.baidu.com/doc/doc.html#_5-%E8%8E%B7%E5%8F%96%E6%8E%88%E6%9D%83%E7%94%A8%E6%88%B7%E4%BF%A1%E6%81%AF) 5. 获取授权用户信息

获取access\_token之后，开发者可以通过access\_token拉取用户信息。 

接口调用请求说明：

    GET https://openapi.baidu.com/rest/2.0/passport/users/getInfo?access_token=access_token 


参数说明：

**参数名 **

**类型 **

**是否必须 **

描述 

access\_token 

string 

是 

由上述步骤获取的OpenAPI接口调用凭证 

get\_unionid

int

否

需要获取unionid时，传递get\_unionid = 1

返回参数：

**参数名**

**参数类型**

**是否必需**

**示例值**

**描述**

openid

string

是

oPXyY4O0ZTmUqSX4MRxYDDCccT6Kc9E

百度用户的唯一标识，对当前开发者帐号、当前应用唯一

unionid

string

否

uA91qQ6gAISTuy0mMqoeh7lZ0w6x478

百度用户统一标识，对当前开发者帐号唯一

userid

uint

否

67411167

老版 百度用户的唯一标识，后续不在返回该字段

securemobile

uint

否

188888888

当前用户绑定手机号（需要向开放平台申请权限）

username

string

否

t\*\*\*e

当前登录用户的展示用户名，包含打码"\*"号

portrait

string

否

e2c1776c31393837313031319605

当前登录用户的头像，头像地址拼接使用方法：https://himg.bdimg.com/sys/portrait/item/{$portrait}

userdetail

string

否

喜欢自由

自我简介，可能为空。

birthday

string

否

1987-01-010000-00-00为未知

生日，以yyyy-mm-dd格式显示。

marriage

string

否

0:未知,1:单身,2:已婚3:恋爱4:离异

婚姻状况

sex

string

否

0:未知,1:男,2:女

性别

blood

string

否

0:未知,1:A,2:B,3:O,4:AB,5:其他

血型

is\_bind\_mobile

uint

否

0:未绑定,1:已绑定

是否绑定手机号

is\_realname

uint

否

0:未实名制,1:已实名制

是否实名制

错误情况下：

**字段名 **

**类型 **

**描述 **

error\_code 

int 

错误码 

error\_msg 

string 

错误描述信息,用来帮助理解和解决发生的错误 

**关于错误码的详细信息请参考附录Ⅰ-5.4** 

返回值示例：

    {    
         "openid": "oPXyY4O0ZTmUqSX4MRxYDDCccT6Kc9E",
         "unionid": "uA91qQ6gAISTuy0mMqoeh7lZ0w6x478",
         "userid": "2097322476",
         "username": "u***9",
         "userdetail": "喜欢自由", 
         "birthday": "1987-01-01",
         "marriage": "0",
         "sex": "1",
         "blood": "3",
         "is_bind_mobile": "1",
         "is_realname": "1" 
    }


出错时返回：

    {  
         "error_code": "100",  
         "error_msg": "Invalid parameter"   
    } 
