<?php
/**
 * Prompt templates for the Generate Blocks plugin
 *
 * @package GENB
 */

namespace GENB;

/**
 * Class to store prompt templates
 */
class Prompts {
    /**
     * Get the system prompt for block generation
     *
     * @return string
     */

    public static function get_block_generation_prompt($imageData, $userPrompt) {
        $prompt = "Create a WordPress block template.\n\n";

        if (!empty($userPrompt)) {
            $prompt .= "Instructions: $userPrompt\n\n";
        }

        if (!empty($imageData)) {
            $prompt .= "Analyze the provided image and recreate its layout and style.\n";
            $prompt .= "If the image contains photos/images that are important for the design:\n";
            $prompt .= "1. Include img tags with placeholder.com URLs matching original dimensions\n";
            $prompt .= "2. Use descriptive alt text for each image\n";
            $prompt .= "3. Add responsive image handling with CSS\n";
            $prompt .= "4. Specify width and height attributes on img tags to prevent layout shifts\n";
            $prompt .= "5. Use srcset for responsive images with 3 sizes (1x, 1.5x, 2x)\n\n";
            $prompt .= "For icons and decorative elements, use CSS where possible instead of images.\n\n";
        }

        $prompt .= <<<'EOT'
IMPORTANT: Return ONLY the code in this EXACT format with NO additional text or comments before or after:

<div class="block-name">
    [Your semantic HTML here]
</div>
<style>
    .block-name {
        [Your CSS here]
    }
</style>
<script>
    [Your JS here]
</script>

IMPORTANT: Always return the full code. DO NOT USE placeholders like `[same code]` or `[Previous HTML content remains exactly the same]`.

Requirements:
1. Add 'zen-edit' with unique descriptive names to editable elements
2. For images, add 'zen-edit' with unique and 'zen-type="image"', for src use existing external urls for best images, when possible or external placeholder image
3. For links, button, etc, add zen-type="url", in addition to 'zen-edit'
4. Keep existing zen-edit names if present
5. Names should be descriptive of the content purpose (e.g. 'hero_title', 'feature_image')
6. Don't add zen-edit to container/wrapper elements unless specifically needed
7. Use ONLY double quotes for HTML attributes
8. Use semantic HTML5 elements. No comments. Clean indentation
9. Include mobile-first responsive design
10. For images use external urls to best images from 'picsum.photos', 'randomuser.me' or 'placeholder.com'
11. If image have overlay, add 'pointer-events: none' style to it. Keep in mind to add right colors to text elements above
12. If font is used, please include a link to it if possible
13. Important: For every child element of block add main block class to its style. Example: .block-name .child-element { ... }
14. Avoid class names like "button-group"
16. Do not use inline styles attributes
17. If you need to include an external script or style - add it inside main `<div></div>` tag.
18. Do not add JavaScript comments.
19. Always add color styles to text elements.
20. Do not use 'zen-edit' as reference in JS or CSS.
21. Always add ";" at the end of each JavaScript function.

Example: If user requests "pricing block", respond with:
<div class="pricing-block">
    <h2 zen-edit="pricing_title">Our Pricing Plans</h2>
    <p zen-edit="pricing_description">Choose the plan that works best for you</p>
    <img zen-edit="pricing_image" zen-type="image" src="https://example.com/pricing-image.jpg" alt="Pricing Image">
    
    <div class="pricing-cards">
        <div class="pricing-card">
            <h3 zen-edit="basic_plan_name">Basic</h3>
            <ul>
                <li zen-edit="basic_feature_1">Feature 1</li>
                <li zen-edit="basic_feature_2">Feature 2</li>
            </ul>
            <div zen-edit="basic_cta_wrap">
                <a href="#" class="button">Get Started</a>
            </div>
        </div>
    </div>
</div>
<style>
    .pricing-block {
        padding: 4rem 2rem;
        text-align: center;
    }
    .pricing-cards {
        display: flex;
        justify-content: center;
    }
    .pricing-card {
        padding: 2rem;
        background: #fff;
    }
</style>
EOT;
        return $prompt;
    }

    /**
     * Get the system prompt for HTML analysis
     *
     * @param string $html HTML content to analyze
     * @return string
     */
    public static function getHtmlAnalysisPrompt($html) {
        $prompt = <<<'EOT'
Analyze the provided HTML and add zen-edit and zen-type attributes to make it editable.

IMPORTANT:
1. Preserve ALL original HTML structure, including:
   - All <link> tags for stylesheets
   - All <script> tags (both inline and external)
   - All styles and classes
   - All external resource references
   - Original formatting and whitespace

2. ONLY make these changes:
   - Add zen-edit attributes to content elements
   - Add zen-type attributes where needed
   - DO NOT modify any other part of the HTML

3. zen-edit and zen-type attributes:
   - zen-edit: Marks element as editable with unique descriptive name
     Example: <h1 zen-edit="hero_title">Title</h1>

   - zen-type: Required for specific elements:
     - Images: <img zen-edit="hero_image" zen-type="image" src="...">
     - Links/buttons: <a zen-edit="cta_link" zen-type="url" href="#">
     - Text elements don't need zen-type

4. Attribute rules:
   - Add zen-edit to content elements (text, images, links)
   - Don't add zen-edit to structural/container elements
   - Use descriptive names (e.g., 'hero_title', 'feature_image')
   - Keep existing zen-edit names if present
   - Use ONLY double quotes for attributes
   - Don't modify any other attributes

5. Return the COMPLETE HTML with all original resources and structure intact.

HTML to analyze:

EOT;
        $prompt .= $html;
        
        $prompt .= "\n\nReturn the COMPLETE HTML with ONLY zen-edit and zen-type attributes added. Make NO other changes.";
        
        return $prompt;
    }
}
