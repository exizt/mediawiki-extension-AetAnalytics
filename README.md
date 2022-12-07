# AetAnalytics

AetAnalytics
* 미디어위키에 '구글 애널리틱스(Google Analytics)'를 적용하고, 몇 가지 설정을 할 수 있는 확장 기능.
* Git : https://github.com/exizt/mw-ext-googleadsense


## Requirements
* PHP 7.4.3 or later (tested up to 7.4.30)
* MediaWiki 1.35 or later (tested up to 1.35)


## cloning a repository
```shell
git clone git@github.com:exizt/mw-ext-AetAnalytics.git AetAnalytics
```


## Installation
1. Download and place the files in a directory called `AetAnalytics` in your `extensions/` folder.
2. Add the following code at the bottom of your `LocalSettings.php`:
```
wfLoadExtension( 'AetAnalytics' );
```


## Configuration
- `$wgAetAnalytics['ga_tag_id']`
    - 구글 애널리틱스의 태그 ID. (eg: `'G-XX..'` or `'UA-XX..'`)
        - type : `string`
        - default : `''`
