SEDOPTION="-i"
if [[ "$OSTYPE" == "darwin"* ]]; then
  SEDOPTION="-i ''"
fi

echo "Enter value to replace 'PluginTemplate' in namespaces and composer.json:"
read replacement
find . -type f -name "*.php" -exec sed $SEDOPTION "s#PluginTemplate#$replacement#g" {} +
find . -type f -name "composer.json" -exec sed $SEDOPTION "s#PluginTemplate#$replacement#g" {} +

echo "Enter value to replace 'plugin-template' with plugin slug in php"
read replacement
find . -type f -name "*.php" -exec sed $SEDOPTION "s#plugin-template#$replacement#g" {} +
find . -type f -name "composer.json" -exec sed $SEDOPTION "s#plugin-template#$replacement#g" {} +

echo "Enter value to replace 'Plugin Template' in php: "
read replacement
find . -type f -regex '.*\.\(php\|txt\)$' -exec sed $SEDOPTION "s#Plugin Template#$replacement#g" {} +

echo "Enter value to replace 'plugin_template()' in php:"
read replacement
find . -type f -name "*.php" -exec sed $SEDOPTION "s#plugin_template()#$replacement#g" {} +

if [ -f "../wordpress-plugin-template.php" ]; then
   cd "$(dirname "$0")"

   PLUGIN_NAME=$(cd ../ && basename "$PWD")

   mv ../sunmi.php ../"$PLUGIN_NAME".php

   sed $SEDOPTION "s#wordpress-plugin-template#$PLUGIN_NAME#g" ./phpcs.sh
   sed $SEDOPTION "s#wordpress-plugin-template#$PLUGIN_NAME#g" ./zip.sh

   echo "Main plugin file wordpress-plugin-template.php renamed to $PLUGIN_NAME.php"
fi
