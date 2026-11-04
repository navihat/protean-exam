<?php

return [

    /*
    | How long one page of the published exam list stays cached (T19, D14).
    | Any exam or question change invalidates it earlier by bumping a version.
    */
    'list_cache_seconds' => (int) env('EXAM_LIST_CACHE_SECONDS', 600),

    /*
    | Seconds still accepted after expires_at before a submission counts as late (D7).
    | Absorbs network delay when the student submits in the last moment.
    */
    'submit_grace_seconds' => (int) env('EXAM_SUBMIT_GRACE_SECONDS', 30),

];
