<?php
/**
 * Configuration settings for the Generate Blocks plugin
 *
 * @package GENB
 */

namespace GENB;

/**
 * Configuration class
 */
class Config {
    /**
     * Available Claude models
     */
    const CLAUDE_MODELS = [
        'claude-3-5-sonnet-20241022'    => [
            'name'        => 'Claude 3.5 Sonnet',
            'description' => 'Most powerful model, best for highly complex tasks'
        ],
        'claude-3-5-haiku-20241022'  => [
            'name'        => 'Claude 3.5 Haiku',
            'description' => 'Excellent balance of intelligence and speed'
        ],
    ];

    /**
     * Available OpenAI models
     */
    const OPENAI_MODELS = [
        'gpt-4o' => [
            'name'        => 'GPT-4o',
            'description' => ''
        ],
        'gpt-4o-mini' => [
            'name'        => 'GPT-4o Mini',
            'description' => ''
        ],
        'o1-mini' => [
            'name'        => 'O1 Mini',
            'description' => ''
        ],
        'o1-preview' => [
            'name'        => 'O1 Preview',
            'description' => ''
        ]
    ];

    /**
     * Claude API configuration
     */
    const CLAUDE_API = [
        'model'            => self::CLAUDE_MODELS,
        'max_tokens'       => 8192,
        'version'         => '2023-06-01',
        'endpoint'         => 'https://api.anthropic.com/v1/messages',
        'timeout'         => 50
    ];

    /**
     * OpenAI API configuration
     */
    const OPENAI_API = [
        'model'            => self::OPENAI_MODELS,
        'max_tokens'       => 8192,
        'version'         => '2024-01-01',
        'endpoint'        => 'https://api.openai.com/v1/chat/completions',
        'timeout'         => 50
    ];
}
