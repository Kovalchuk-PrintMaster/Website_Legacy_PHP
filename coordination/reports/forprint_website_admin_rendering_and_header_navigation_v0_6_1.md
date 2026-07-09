
ForPrint Website — Admin Rendering and Header Navigation v0.6.1 Report
Status

admin_rendering_and_header_navigation_v0_6_1_completed

Completed
Admin rendering no longer fails on TinyMCE footer implode() under PHP 8.2.
Admin show page opens locally after login.
Public navigation now uses corrected information.where filter.
Existing admin-controlled show_top_menu field is preserved as the first navigation control mechanism.
New navigation table remains deferred.
Checks

Required before commit:

php -l targeted admin/public files
make site-smoke
make check
git diff --check

If local server is already running on 8098, use:

FP_WEB_LOCAL_HTTP_PORT=8099 make site-smoke
Known notes
/admin may still redirect through /admin/show; this is acceptable for now if /admin/show renders.
Warnings remain in legacy code and should be cleaned gradually when they block work.
Cart remains a separate checkpoint.
Next checkpoint

ForPrint Website — Header Visual Simplification v0.6.2
