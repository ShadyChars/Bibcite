<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor\autoload.php';

/**
 * A static class used to download URLs and cache them to specified disk locations.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @since 1.0.0
 */
class Downloader
{
    // How long should we wait between HTTP requests (= 5 mins)?
    private const HTTP_DORMANCY_SECONDS = 300;
    
    // How long should our transients live (= 30 days)?
    private const TRANSIENT_EXPIRATION_SECONDS=3600*24*30;
    
    /**
     * Retrieve a specified URL to a specified file. Where possible, HTTP requests and/or data
     * downloads are minimised.
     *
     * @param string $url URL to retrieve
     * @param string $filename filename to receive the retrieved URL
     * @param boolean $force_download should we always retrieve the complete file? If false, a 
     * cached or otherwise unchanged version may be used.
     * @return boolean true if a new version of the file was cached and false otherwise
     * 
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function save_url_to_file(
        string $url, string $filename, $force_download = false
    ) : bool {

        // Include the filename in our DB prefixes to scope our data
        $slugify = new \Cocur\Slugify\Slugify();
        $filename_slug = $slugify->slugify($filename);
        $transient_prefix = BIBCITE_SC_PREFIX . "." . $filename_slug . ".";

        // Get the last known ETag for this file, if any
        $etag_transient_name = $transient_prefix . "last-etag";
        $last_etag = get_transient($etag_transient_name);
        if ($last_etag != false) {
            Logger::instance()->info("Last URL ETag: " . $last_etag);
        }

        // Get the last known download time for this file, if any
        $last_downloaded_time_transient_name = $transient_prefix . "last-downloaded-time";
        $last_downloaded_time_string = get_transient($last_downloaded_time_transient_name);
        $last_downloaded_time = null;
        if ($last_downloaded_time_string !== false) {
            $last_downloaded_time = intval($last_downloaded_time_string);
            Logger::instance()->info(
                "URL last downloaded: " . date(DATE_RFC850, $last_downloaded_time)
            );
        }

        // If we know when we last downloaded this file, we may be able to skip a repeated download.
        if (isset($last_downloaded_time)) {
            if ($force_download) {
                Logger::instance()->debug("Ignoring last download time.");
            } else if ($last_downloaded_time >= (time() - self::HTTP_DORMANCY_SECONDS)) {
                Logger::instance()->debug(
                    "URL last downloaded within " .
                    self::HTTP_DORMANCY_SECONDS .
                    " seconds. Skipping update."
                );
                return false;
            }
        }

        Logger::instance()->info("Getting URL (${url}) and saving to file (${filename})...");

        // Get the resource, but only if it has an ETag different from the last one we saw.
        $headers = [];
        $curl_error = null;
        $start_get_time = time();
        try {

            // If the target directory doesn't exist, create it.
            $dirname = dirname($filename);
            if (!is_dir($dirname)) {
                mkdir($dirname);
            }

            $fp = fopen($filename, 'w');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FILE, $fp); // Write to file
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirection
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL certs

            // Capture headers.
            curl_setopt(
                $ch,
                CURLOPT_HEADERFUNCTION,
                function ($curl, $header) use (&$headers) {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2) // ignore invalid headers
                    {
                        return $len;
                    }

                    $name = strtolower(trim($header[0]));
                    $value = trim($header[1]);
                    if (!array_key_exists($name, $headers)) {
                        $headers[$name] = [$value];
                    } else {
                        $headers[$name][] = $value;
                    }

                    return $len;
                }
            );

            // Only get the body of the resource if its ETag is different or if we're forcing a
            // download.
            if (!$force_download && $last_etag) {
                Logger::instance()->debug("Using ETag (${last_etag})");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("If-None-Match: ${last_etag}"));
            }

            curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);

        } catch (Exception $e) {
            Logger::instance()->error("Failed to get URL (${url}): " . $e->getMessage() . ".");
            return false;
        }

        // If there's an error, report as much info as possible and quit now.
        if ($curl_error) {
            Logger::instance()->error("Error getting URL: ${curl_error}. Skipping update.");
            return false;
        }

        // The request succeeded. Update our ETag and our last GET datetime.
        $new_etag = array_key_exists("etag", $headers) ? $headers["etag"][0] : null;
        $new_download_time = time();
        set_transient(
            $etag_transient_name,
            $new_etag,
            self::TRANSIENT_EXPIRATION_SECONDS
        );
        set_transient(
            $last_downloaded_time_transient_name,
            $new_download_time,
            self::TRANSIENT_EXPIRATION_SECONDS
        );

        $last_updated_string = date(DATE_RFC850, $new_download_time);
        Logger::instance()->debug(
            "Writing Etag (${new_etag}) and download time (${new_download_time})."
        );

        // Did we get a new copy of the resource? If not, nothing more to do.
        $file_mtime = filemtime($filename);
        if ($file_mtime && $file_mtime < $start_get_time) {
            Logger::instance()->debug("Cached file has not changed. Using existing copy.");
            return false;
        }

        // Done.
        return true;
    }
}
