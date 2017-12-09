<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>邮箱验证</title>
</head>
<body>
<table class="table" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; border-spacing: 0; border-collapse: collapse; table-layout: fixed; mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #313a45;" bgcolor="#f4f4f4">
    <tbody>
    <tr style="padding: 0;">
        <td align="center" valign="top" style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; word-break: break-word; -webkit-hyphens: auto; -ms-hyphens: auto; hyphens: auto; border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0; padding: 20px 10px;">
            <table border="0" cellpadding="0" cellspacing="0" width="580" class="email-body" style="border-spacing: 0; border-collapse: collapse; table-layout: auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-radius: 8px; -webkit-box-shadow: 1px 2px 5px rgba(1,1,1,0.16); box-shadow: 1px 2px 5px rgba(1,1,1,0.16); padding: 0;" bgcolor="#fff">
                <tbody>
                <tr style="padding: 0;">
                    <td align="center" valign="middle" class="header" style="word-break: break-word; -webkit-hyphens: auto; -ms-hyphens: auto; hyphens: auto; border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-radius: 8px 8px 0 0; height: 55px; padding: 0;" bgcolor="#313945">
                        <a target="_blank" href="#" style="color: #00b2b2; text-decoration: none;">
                        </a>
                    </td>
                </tr>
                <tr style="padding: 0;">
                    <td align="left" valign="top" class="content" style="word-break: break-word; -webkit-hyphens: auto; -ms-hyphens: auto; hyphens: auto; border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; padding: 10px 25px 25px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-spacing: 0; border-collapse: collapse; table-layout: auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt; padding: 0;">
                            <tbody>
                            <tr style="padding: 0;">
                                <td align="center" valign="middle" style="word-break: break-word; -webkit-hyphens: auto; -ms-hyphens: auto; hyphens: auto; border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; padding: 0;">
                                    <h1 style="word-break: normal; height:50px;line-height: 50px; font-size: 20px; font-weight: bold; margin: 0;">您好，{{$name}}</h1>
                                    <p class="lead" style="font-weight: normal; height:40px;line-height: 40px; font-size:18px;margin: 0;">感谢您注册和使用我们的服务，</p>
                                    <p class="lead" style="font-weight: normal; height:40px;line-height: 40px; font-size:18px;padding-bottom:10px;margin: 0;">请点击以下链接激活您的账号！</p>
                                    <table border="0" cellpadding="0" cellspacing="0" width="250" class="button-block" style="border-spacing: 0; border-collapse: separate; table-layout: auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 250px; padding: 0;margin-bottom:15px;">
                                        <tbody>
                                        <tr style="padding: 0;">
                                            <td align="center" valign="middle" class="button" style="word-break: break-word; -webkit-hyphens: auto; -ms-hyphens: auto; hyphens: auto; border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-radius: 28px; padding: 15px 20px;" bgcolor="#00c9c9">
                                                <a href="{{$url}}" style="color: #fff !important; text-decoration: none; display: block; font-size: 20px; font-style: italic;" target="_blank">马上激活账号</a>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <p class="lead" style="font-weight: normal; height:30px;line-height: 30px; font-size:16px;margin: 0;">温馨提示：如果这个请求不是由您发起的，</p>
                                    <p class="lead" style="font-weight: normal; height:30px;line-height: 30px; font-size:16px;margin: 0;">请不用担心，您可以安全地忽略这封邮件。</p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr style="padding: 0;">
                    <td align="center" valign="middle" class="footer" style="word-break: break-word; -webkit-hyphens: auto; -ms-hyphens: auto; hyphens: auto; border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt;padding: 0 10px 20px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-spacing: 0; border-collapse: collapse; table-layout: auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt; padding: 0;">
                            <tbody>
                            <tr style="padding: 0;">
                                <td align="center" valign="middle" class="close-text" style="word-break: break-word; -webkit-hyphens: auto; -ms-hyphens: auto; hyphens: auto; border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-size: 14px; border-top-width: 1px; border-top-color: #ccc; border-top-style: solid; padding: 5px 0 0;">
                                    <p style="color: #333; padding-top:10px;height:25px;line-height: 25px; margin:0">获取更多帮助，请联系我们的支持团队。</p>
                                    <P style="height:25px;line-height: 25px; margin:0">
                                        服务邮箱：<a target="_blank" href="mailto:service@example.com" style="color: #00b2b2; text-decoration: none;">service@example.com</a>
                                    </P>
                                    <P style="height:25px;line-height: 25px; margin:0">
                                        <a target="_blank" href="%%user_defined_unsubscribe_link%%" style="font-size:12px;color: #999; text-decoration: none;">我不想再收到此类邮件</a>
                                    </P>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>