<?php
/*
   Plugin Name: WP-GPT
   Plugin URI: n/a
   Description: Allows users to interact with the OpenAI Chat Completion API from their WordPress dashboard.
   Version: 1.0.0
   Author: Ian Schoonover
   Author URI: https://www.devsprout.io/
   License: GPL v2 or later
   License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

add_action('admin_menu', 'wp_gpt_menu');

function wp_gpt_menu() {
    add_menu_page(
        'OpenAI Chat', // page title
        'OpenAI Chat', // menu title
        'manage_options', // capability
        'openai-chat', // menu slug
        'wp_gpt_page' // callback function
    );
}

function wp_gpt_page() {
    ?>
       <div class="wrap">
           <h1>OpenAI Chat</h1>
           <form method="post">
               <textarea name="openai_chat_query" required></textarea>
               <input type="submit" value="Send">
           </form>
       </div>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' ) {
        if (isset($_POST['openai_chat_query'])) {
            $query = sanitize_text_field($_POST['openai_chat_query']);
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . getenv('OPENAI_API_KEY'),
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'model' => 'gpt-3.5-turbo-1106',
                    'temperature' => 0.5,
                    'max_tokens' => 1024,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful WordPress developer assistant.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $query
                        ]
                    ],
                ]),
                'timeout' => 60, // Increase timeout length to 60 seconds
            ]);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo "Something went wrong: $error_message";
            } else {
                $api_response = json_decode(wp_remote_retrieve_body($response), true);
                error_log(print_r($api_response, true));
                if (isset($api_response['choices'][0]['message']['content'])) {
                    $chat_response = $api_response['choices'][0]['message']['content'];
                    echo $chat_response;
                } else {
                    echo "Received unexpected response structure from API.";
                }
            }
        }
    }
}