<?php

declare(strict_types=1);

use ArabicSupport\Enums\SlugMode;

return [

    /*
    |--------------------------------------------------------------------------
    | Slug Configuration
    |--------------------------------------------------------------------------
    |
    | Controls how Arabic slugs are generated.
    |
    | mode:
    |   SlugMode::Unicode => keeps Arabic letters in the slug.
    |   SlugMode::Ascii   => transliterates Arabic text to ASCII.
    |
    | separator:
    |   The character used between words.
    |
    | max_words:
    |   Limits the number of words used in generated slugs.
    |
    | max_length:
    |   Limits the final slug length.
    |
    */

    'slug' => [
        'mode' => SlugMode::Unicode,
        'separator' => '-',
        'max_words' => 8,
        'max_length' => 180,
    ],

    /*
    |--------------------------------------------------------------------------
    | Normalization
    |--------------------------------------------------------------------------
    |
    | These options are used by Laravel integration helpers when generating
    | search keys, slugs, cleaned values, or normalized model attributes.
    |
    | Core PHP methods can still override behavior explicitly through method
    | arguments and enums.
    |
    */

    'normalization' => [
        'strip_diacritics' => true,
        'strip_tatweel' => true,
        'normalize_digits' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Unicode Security
    |--------------------------------------------------------------------------
    |
    | Controls whether security cleaners should remove hidden Unicode characters
    | such as bidirectional controls, zero-width marks, and invisible formatting
    | characters.
    |
    | These characters can be useful in rare typographic cases, but they may also
    | be abused in usernames, filenames, slugs, comments, and imported content.
    |
    */

    'security' => [
        'remove_bidi_controls' => true,
        'remove_invisible_characters' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Profanity / Blocked Words
    |--------------------------------------------------------------------------
    |
    | The package does not ship with a predefined blocked-word list because such
    | lists are project-specific, dialect-sensitive, and context-sensitive.
    |
    | The "words" option accepts any of these forms:
    |
    |   1. An array of words:
    |      'words' => ['word_one', 'word_two'],
    |
    |   2. A TXT file path:
    |      'words' => base_path('resources/profanity/arabic.txt'),
    |
    |      TXT format:
    |        - One word per line
    |        - Empty lines are ignored
    |        - Lines starting with "#" are ignored
    |
    |   3. A JSON file path:
    |      'words' => base_path('resources/profanity/arabic.json'),
    |
    |      JSON may be:
    |        ["word_one", "word_two"]
    |
    |      or:
    |        {"words": ["word_one"], "files": ["/path/to/more.txt"]}
    |
    |   4. A mixed array of words and files:
    |      'words' => [
    |          'manual_word',
    |          base_path('resources/profanity/common.txt'),
    |          base_path('resources/profanity/custom.json'),
    |      ],
    |
    | Duplicate file paths and duplicate words are ignored by the loader.
    |
    */

    'profanity' => [
        'words' => [],
    ],

];
