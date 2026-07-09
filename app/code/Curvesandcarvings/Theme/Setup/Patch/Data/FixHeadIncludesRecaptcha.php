<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ScopeInterface as AppScopeInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Fix design/head/includes reCAPTCHA script: use RequireJS jQuery instead of global $.
 */
class FixHeadIncludesRecaptcha implements DataPatchInterface
{
    private const CONFIG_PATH = 'design/head/includes';

    public function __construct(
        private readonly WriterInterface $configWriter,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function apply(): void
    {
        $fixed = <<<'HTML'
<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		  ga('create', 'UA-59439058-1', 'auto');
		  ga('send', 'pageview');
		</script>

<script src="https://www.google.com/recaptcha/api.js?render=6LdI7vYfAAAAAMts-_wKdXW__bzKkv4UMBUYoG9G"></script>
<script>
require(['jquery'], function ($) {
    grecaptcha.ready(function () {
        grecaptcha.execute('6LdI7vYfAAAAAMts-_wKdXW__bzKkv4UMBUYoG9G', { action: 'submit' }).then(function (token) {
            $('form').each(function () {
                $(this).find('input[name="g-recaptcha-response"]').remove();
                $(this).append('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');
            });
        });
    });
});
</script>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-LGYPD47FY5"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-LGYPD47FY5');
</script>
HTML;

        $this->configWriter->save(self::CONFIG_PATH, $fixed, AppScopeInterface::SCOPE_DEFAULT, 0);

        foreach ($this->storeManager->getStores() as $store) {
            $this->configWriter->save(
                self::CONFIG_PATH,
                $fixed,
                ScopeInterface::SCOPE_STORES,
                (int) $store->getId()
            );
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
