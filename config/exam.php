<?php

return [

    /*
    | How long one page of the published exam list stays cached (T19, D14).
    | Any exam or question change invalidates it earlier by bumping a version.
    */
    'list_cache_seconds' => (int) env('EXAM_LIST_CACHE_SECONDS', 600),

];
