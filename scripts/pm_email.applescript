-- PM Agent Email Script — diadaptasi untuk Notula GKJ Jakarta dari skill Sunartha
-- Usage: osascript pm_email.applescript "to_recipient" "subject" "body"
-- Beda dari versi asli: TIDAK ada CC ke tim Sunartha (dikonfirmasi pengguna 8 Agustus 2026).
-- Kalau nanti ingin CC lagi, tambahkan kembali blok ccList seperti versi asli di
-- gitlab.sunartha.co.id/products/sunartha-claude-skills-dev/-/blob/main/scripts/pm_email.applescript

on run argv
    set recipientEmail to item 1 of argv
    set emailSubject to item 2 of argv
    set emailBody to item 3 of argv

    tell application "Mail"
        set newMessage to make new outgoing message with properties {subject:emailSubject, content:emailBody, visible:false}
        tell newMessage
            make new to recipient at end of to recipients with properties {address:recipientEmail}
        end tell
        send newMessage
    end tell

    return "Email terkirim ke " & recipientEmail
end run
