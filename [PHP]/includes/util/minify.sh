#!/bin/bash

cd ../css

for i in * ; do
	case "$i" in
		*.min.css)
			rm -f $i ;;
	esac
done

for i in * ; do
	case "$i" in
		*.css)
			echo java -jar ../util/yuicompressor-2.4.8.jar $i -o ${i::-4}.min.css
			java -jar ../util/yuicompressor-2.4.8.jar $i -o ${i::-4}.min.css ;;
	esac
done

cd ../js

for i in * ; do
	case "$i" in
		jquery.min.js)
			echo Não excluir o $i original ;;
		*.min.js)
			rm -f $i ;;
	esac
done

for i in * ; do
	case "$i" in
		"[AJAX STRUCTURE].js")
			echo Ignorando $i ;;
		*.min.js)
			echo Manter o $i original ;;
		jquery.js)
			echo Não "comprime" o $i original ;;
		*.js)
			echo java -jar ../util/yuicompressor-2.4.8.jar $i -o ${i::-3}.min.js
			java -jar ../util/yuicompressor-2.4.8.jar $i -o ${i::-3}.min.js ;;
	esac
done

cd ../../editor

for i in * ; do
	case "$i" in
		*.min.js)
			rm -f $i ;;
	esac
done

for i in * ; do
	case "$i" in
		*.js)
			echo java -jar ../include/util/yuicompressor-2.4.8.jar $i -o ${i::-3}.min.js
			java -jar ../include/util/yuicompressor-2.4.8.jar $i -o ${i::-3}.min.js ;;
	esac
done

cd ../include/util
