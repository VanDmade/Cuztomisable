<?php

return [
    // Default country that will be selected when filling out the form, set to null if you want them to choose.
    'default_country' => 'USA',
    /* List of all allowed countries and their states/provinces. If the states/provinces are left blank then that
     * dropdown will be removed. If you only have one option, then they will not be able to select a country and it
     * will be defaulted */
    'countries' => [
        [
            'value' => 'USA',
            'text' => 'United States',
            // Whether the city input should exist
            'city' => true,
            // List of all states / provinces to be displayed when a country is picked
            'states_or_provinces' => [
                ['value' => 'AL', 'text' => 'Alabama'],
                ['value' => 'AK', 'text' => 'Alaska'],
                ['value' => 'AZ', 'text' => 'Arizona'],
                ['value' => 'AR', 'text' => 'Arkansas'],
                ['value' => 'CA', 'text' => 'California'],
                ['value' => 'CO', 'text' => 'Colorado'],
                ['value' => 'CT', 'text' => 'Connecticut'],
                ['value' => 'DE', 'text' => 'Delaware'],
                ['value' => 'DC', 'text' => 'District Of Columbia'],
                ['value' => 'FL', 'text' => 'Florida'],
                ['value' => 'GA', 'text' => 'Georgia'],
                ['value' => 'HI', 'text' => 'Hawaii'],
                ['value' => 'ID', 'text' => 'Idaho'],
                ['value' => 'IL', 'text' => 'Illinois'],
                ['value' => 'IN', 'text' => 'Indiana'],
                ['value' => 'IA', 'text' => 'Iowa'],
                ['value' => 'KS', 'text' => 'Kansas'],
                ['value' => 'KY', 'text' => 'Kentucky'],
                ['value' => 'LA', 'text' => 'Louisiana'],
                ['value' => 'ME', 'text' => 'Maine'],
                ['value' => 'MD', 'text' => 'Maryland'],
                ['value' => 'MA', 'text' => 'Massachusetts'],
                ['value' => 'MI', 'text' => 'Michigan'],
                ['value' => 'MN', 'text' => 'Minnesota'],
                ['value' => 'MS', 'text' => 'Mississippi'],
                ['value' => 'MO', 'text' => 'Missouri'],
                ['value' => 'MT', 'text' => 'Montana'],
                ['value' => 'NE', 'text' => 'Nebraska'],
                ['value' => 'NV', 'text' => 'Nevada'],
                ['value' => 'NH', 'text' => 'New Hampshire'],
                ['value' => 'NJ', 'text' => 'New Jersey'],
                ['value' => 'NM', 'text' => 'New Mexico'],
                ['value' => 'NY', 'text' => 'New York'],
                ['value' => 'NC', 'text' => 'North Carolina'],
                ['value' => 'ND', 'text' => 'North Dakota'],
                ['value' => 'OH', 'text' => 'Ohio'],
                ['value' => 'OK', 'text' => 'Oklahoma'],
                ['value' => 'OR', 'text' => 'Oregon'],
                ['value' => 'PA', 'text' => 'Pennsylvania'],
                ['value' => 'RI', 'text' => 'Rhode Island'],
                ['value' => 'SC', 'text' => 'South Carolina'],
                ['value' => 'SD', 'text' => 'South Dakota'],
                ['value' => 'TN', 'text' => 'Tennessee'],
                ['value' => 'TX', 'text' => 'Texas'],
                ['value' => 'UT', 'text' => 'Utah'],
                ['value' => 'VT', 'text' => 'Vermont'],
                ['value' => 'VA', 'text' => 'Virginia'],
                ['value' => 'WA', 'text' => 'Washington'],
                ['value' => 'WV', 'text' => 'West Virginia'],
                ['value' => 'WI', 'text' => 'Wisconsin'],
                ['value' => 'WY', 'text' => 'Wyoming'],

            ],
        ]
    ],
    'default_country_code' => 1,
    // List of allowed country codes and the country they belong to. The format will be used to replace X with digits
    'country_codes' => [
        ['text' => '(+1) USA', 'value' => 1, 'format' => '(XXX) XXX-XXXX', 'required_length' => 10],
        ['text' => '(+44) UK', 'value' => 44, 'format' => '(XX) XXXX-XXXX', 'required_length' => 10],
    ],
];