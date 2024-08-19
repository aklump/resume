<?php


namespace AKlump\Resume;

use AKlump\LoftLib\Code\Dates;
use AKlump\LoftLib\Component\Storage\FilePath;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Backend
 *
 * Generate JSON static files from yaml data files to be consumed by the front end.
 *
 * @package AKlump\resume
 */
class Builder {

    /**
     * @var string The path to the source yml/data files.
     */
    protected $dataPaths;

    /**
     * @var The name of the directory in themes to use for rendering the html.
     */
    protected $themeDir;

    /**
     * @var string The path to the api server directory, where we write the json files.
     */
    protected $outputPath;

    /**
     * Builder constructor.
     *
     * @param array An array of FilePath objects where the data for the resume can be found.  A file of the same name
     *                         in a latter directory will replace it's match in the former, which allows overrides.
     * @param string $themeDir The path to the directory containing the theme.
     */
    public function __construct(array $dataPaths, string $themeDir)
    {
        $this->dataPaths = $dataPaths;
        $this->themeDir = $themeDir;
    }

    /**
     * @param \AKlump\LoftLib\Component\Storage\FilePath $outputPath The json file path to write the data to.
     */
    public function writeDataAsJson(FilePath $outputPath)
    {
        $outputPath->putJson($this->getData(), JSON_PRETTY_PRINT)->save();
    }

    /**
     * Convert data from logical key/values in the human yml interface to semantic structure more suitable for front end
     * consumption.
     *
     * @return array
     */
    public function getData()
    {
        // Read in the files overriding any matching names with the latter.
        $files = [];
        array_walk($this->dataPaths, function ($dir) use (&$files) {
            if (!is_dir($dir)) {
                throw new \RuntimeException("Cannot load data from: \"$dir\"");
            }
            $dir = new FilePath($dir);
            foreach ($dir->children('/\.yml$/')->all() as $data) {
                $files[$data->getFilename()] = $data;
            }
        });

        // Load and parse the final file list from yaml.
        $data = [];
        array_walk($files, function ($file) use (&$data) {
            $name = $file->getFilename();
            $data[$name] = Yaml::parse(file_get_contents($file->getPath()));
            $data[$name]['id'] = $name;
        });

        // Convert to a more consumable data structure.
        if (empty($data['contact'])) {
            throw new \RuntimeException("Resume cannot be built without \"contact.yml\"");
        }
        $apiReady = [
            'contact' => $data['contact'],
            'position' => $data['position'],
        ];
        unset($data['contact']);
        unset($data['position']);

        uasort($data, function ($a, $b) {
            return ($a['sort'] ?? 0) - ($b['sort'] ?? 0);
        });

        $apiReady['sections'] = array_values(array_map(function ($item) {
            unset($item['sort']);

            if (isset($item['body'])) {
                $item['text'] = $item['body'];
                unset($item['body']);
            }
            else if (isset($item['jobs'])) {
                $item['list_item_type'] = 'job';
                $item['list'] = $item['jobs'];
                unset($item['jobs']);
                array_walk($item['list'], function (&$item) {
                    $this->handleDate('start', $item);
                    $this->handleDate('stop', $item);
                });
            }
            else if (isset($item['schools'])) {
                $item['list_item_type'] = 'school';
                $item['list'] = $item['schools'];
                unset($item['schools']);
            }
            else if (isset($item['list'])) {
                $item['list_item_type'] = 'text';
            }

            return $item;
        }, $data));

        return $apiReady;
    }

    /**
     * Return the html markup for a given media type.
     *
     * @param $media
     *
     * @return string
     */
    public function getHtml($document, $media)
    {
        $loader = new \Twig_Loader_Filesystem("$this->themeDir/templates");
        $twig = new \Twig_Environment($loader);

        /**
         * Wrap words with span for css theming.
         */
        $enspan = new \Twig_Filter('enspan', function ($string) {
            return '<span>' . implode('</span><span>', explode(' ', $string)) . '</span>';
        }, ['is_safe' => ['html']]);
        $twig->addFilter($enspan);

        /**
         * Wrap a string with a link when in website mode.
         */
        $enlink = new \Twig_Filter('enlink', function ($string) use ($media, $enspan) {
            $domain = preg_replace('/^https?:\/\//', '', $string);

            return '<a href="' . $string . '" target="_blank">' . $domain . '</a>';
        }, ['is_safe' => ['html']]);
        $twig->addFilter($enlink);

        /**
         * Remove private values when in website mode.
         */
        $private = new \Twig_Filter('private', function ($string) use ($media) {
            if ('website' !== $media) {
                return $string;
            }

            return '';
        }, ['is_safe' => ['html']]);
        $twig->addFilter($private);

        $titles = [
            'letter' => 'Cover Letter',
            'resume' => 'Resume',
        ];

        return $twig->render('index.twig', $this->getData() + [
                'document' => [
                    'id' => $document,
                    'title' => $titles[$document],
                ],
                'media' => $media,
            ]);
    }

    private function handleDate($key, &$item)
    {
        if (!empty($item[$key])) {
            try {
                $value = $item[$key] ?? 'present';
                $dates = new Dates('America/Los_Angeles');
                $date = $dates->l($dates->normalizeToOne($value));
                $item[$key] = $date->format('n/Y');

                $period = Dates::z($dates->now())->diff(Dates::z($date));
                $item[$key . '_period'] = [];
                $item[$key . '_period'][] = ($p = $period->format('%y')) === 1 ? '1 year' : "$p years";
                $item[$key . '_period'][] = ($p = $period->format('%m')) === 1 ? '1 month' : "$p months";
                $item[$key . '_period'] = implode(', ', $item[$key . '_period']);

                $item[$key . '_raw'] = Dates::z($date)->format(DATE_ISO8601);

                return;
            } catch (\Exception $exception) {
                // Purposefully left blank
            }
        }
        $item[$key] = null;
        $item[$key . '_raw'] = null;
    }
}
