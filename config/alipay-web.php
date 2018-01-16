<?php
/**
 * Created by PhpStorm.
 * User: luojinyi
 * Date: 2017/6/26
 * Time: 下午5:21
 */

return [
    //应用ID,您的APPID。
    'app_id' => "2016080600182076",

    //商户私钥 不能用pkcs8.pem中的密钥！！！！！
    'merchant_private_key' => "MIIEowIBAAKCAQEAut6d7VexO3rzC3wIwTgOwAGTjrKV3D8f4OCYYXMLWtjR6LcxJ1Kg9rdZctFw7zFD2fZofQvTMtxF2WGLrG6SpQtqq9O2Kxqtvr1sSU1rY6hzmy948L36tVVHigJ9xFKfLRZ9aY3r6q7QMuHIuY/fWfafOIa1j8LstXetZx7EUl/nDD2bOJ/bizk0JDKwhHDhaHMkE909Ida+YUfyTyR2A3Wl2oBPlRtZ0KGo8T7AmcwAn1crp1M20GcOKFrdZezI94tjcSOcOcWoNmQ/Ht8zyzCYOQ1Z97HkYqbVcQ4fMGMa/zHW7n8H8kD7nsT9v2JSlnwl9r5o98BnQLoyL8qY8wIDAQABAoIBAQCsYnrbnP7ROQ2Gdah53iW1OH8Pr20RnWXiBC5uDzvVVZjmjkAY31+/2Mn6Yn1FGnRWWLpxSHxPOeZxr0uzCNncyRhhZE9zmgvXnlSLlpDPgXEMIpH1u7vQldgF12B/wBw48rtEqXiNqTBFEAYkMDXBfwHImnRZJ19H1Bwxua8vsNXOVBZTCFG8ibXd7y/y7+MDFKUMUUGaCO//mTWexW2ZyuNRmfZAYgf2ZKp8eJtQTkrxkPwgIV9S0tgQ8Nw0gAfehEQDkiID99ahOWbH0pjMNi+ZJ22XIegN3zEQwDjM7RppgwGHWUPWAXU1cavO/vRdSZe0XqXGSwPZ0cmf1g+RAoGBAO0k9EUEt1CISMQVJo+wuTiSU7vqSqZc7DBGjJWzATaUY0ijrQdm+hml0h76ElA8InI6e99FQUAqJHU0UuC0I0yW6RHaOOYJV1eGs8pOOGJ5I6kuJATb7AM325lXPQThRAXKcTRoGkkXI2E5i3jyKFBhOtj6IJ9+7+Jky8nhdHgpAoGBAMm6U27b+DUD7rIXfIBqBvHlqy4c6zyb22iQZrGXKGZFX5Yvpqof/oS95QiIFmqpmHPOgoMxX06vs8IoxFQ4+EDeckgA5ShqlBm+lw3VSFDLTH9TGpes43OxCVdYZnYAHbn0wHPeYrMUMKg1X0AkXQaUxnKcFk9+4iIvbDoaBpu7AoGAWC6vrqKMUCP9evyqdDAxD/pJrMz4qVhQc7soN9pyjwsqyC4k/2WphrQEqIQxHyjdXkClI2crVmLVX8fvMaOKpMZRMNRG6DN9CQ0L+iSQDv4g5p5DwpLM9n1k6WZpSFFD7CtVYTl83MT/4P13Aah6mTkumtSYIPcg2LzGkvGFT9ECgYAe9ZOcbfH34PSfLG+VHbCXK1JML3ACy0CvGYpJMxOmLntvBz0i2uoq4SHnX0thb0nQbB8nl0ozP0/tOmh3LI8dFYagelKxEzpLgS6ZluQUUj8ZQ13PbV8zADaYyx5eecIWKfPnVHf9V9nn1tkZdPs56VdTe5UI+kYmTPTlLd1ZxQKBgAiHwa2bhyDjkJXEybyJc49+f09oryQxOqVRzqG02aH+HazVIQvLtSzYtemYiRi5XtSLegsGCVrpy3/z8iZ2p9UOSkBEpICKfmb11IGqV1sj9opyxo8e8QqJsddlZmfHLOo35hGvvbJiLCPHUWw75Jye3DbsNkrWo+0pu71tIGpa",


    //异步通知地址post
    'notify_url' => "http://外网可访问网关地址/alipay.trade.page.pay-PHP-UTF-8/notify_url.php",

    //同步跳转get
    //'return_url' => "http://外网可访问网关地址/alipay.trade.page.pay-PHP-UTF-8/return_url.php",
    'return_url' => "http://www.quanzhan.com/home/shop/cart_complete",

    //编码格式
    'charset' => "UTF-8",

    //签名方式
    'sign_type' => "RSA2",

    //支付宝网关
    'gatewayUrl' => "https://openapi.alipaydev.com/gateway.do",

    //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
    'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAut6d7VexO3rzC3wIwTgOwAGTjrKV3D8f4OCYYXMLWtjR6LcxJ1Kg9rdZctFw7zFD2fZofQvTMtxF2WGLrG6SpQtqq9O2Kxqtvr1sSU1rY6hzmy948L36tVVHigJ9xFKfLRZ9aY3r6q7QMuHIuY/fWfafOIa1j8LstXetZx7EUl/nDD2bOJ/bizk0JDKwhHDhaHMkE909Ida+YUfyTyR2A3Wl2oBPlRtZ0KGo8T7AmcwAn1crp1M20GcOKFrdZezI94tjcSOcOcWoNmQ/Ht8zyzCYOQ1Z97HkYqbVcQ4fMGMa/zHW7n8H8kD7nsT9v2JSlnwl9r5o98BnQLoyL8qY8wIDAQAB",

    //支付时提交方式 true 为表单提交方式成功后跳转到return_url,
    //false 时为Curl方式 返回支付宝支付页面址址 自己跳转上去 支付成功不会跳转到return_url上， 我也不知道为什么，有人发现可以跳转请告诉 我一下 谢谢
    // email: 40281612@qq.com
    'trade_pay_type' => true,
];
