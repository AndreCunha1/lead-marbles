/*
* WYSIWYG Resize (http://editorboost.net/WYSIWYGResize)
* Copyright 2012 Editorboost. All rights reserved.
*
* Webkitresize commercial licenses may be obtained at http://editorboost.net/home/licenses.
* If you do not own a commercial license, this file shall be governed by the
* GNU General Public License (GPL) version 3. For GPL requirements, please
* review: http://www.gnu.org/copyleft/gpl.html
*
* Version date: Apr 14 2013
* REQUIRES: jquery 1.7.1+
*/

; (function ($) {
    $.fn.wysiwygResize = function (options) {
        return this.each(function () {
            var settings = $.extend({
                selector: "div, span"
            }, options);

            var lastCrc;
            var elementResizeinProgress = false;
            var currentElement;

            var methods = {

                guidGenerator: function() {
                    var S4 = function() {
                       return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
                    };
                    return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
                },

                guidFilter: function(context){
                    return "[data-guid='" + context.guid + "']";
                },

                removeResizeElements: function (context) {
                    $(".wysiwygResize-selector, .wysiwygResize-selector-inner").filter(methods.guidFilter(context)).remove();
                    $(".wysiwygResize-region").filter(methods.guidFilter(context)).remove();
                },

                elementClick: function (context, element) {
                    if (settings.beforeElementSelect) {
                        settings.beforeElementSelect(element);
                    }

                    methods.removeResizeElements(context);
                    currentElement = element;

                    var elementHeight = $(element).outerHeight();
                    var elementWidth = $(element).outerWidth();
                    var iframePos = context.$ifrm.offset();
                    var elementPosition = $(element).offset();
                    var ifrmScrollTop = context.$ifrmBody.scrollTop();
                    var ifrmScrollLeft = context.$ifrmBody.scrollLeft();

                    if($.browser.msie){
                        ifrmScrollTop = $(context.ifrm.contentWindow.document).scrollTop();
                        ifrmScrollLeft = $(context.ifrm.contentWindow.document).scrollLeft();
                    }

                    context.$docBody.append("<span data-guid='" + context.guid + "' class='wysiwygResize-selector' style='display:none;background-color:white;opacity:0;filter:alpha(opacity=0);position:absolute;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop + elementHeight - 100) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft + elementWidth - 100) + "px;width:6px;height:6px;/*z-index:1000;*/padding:100px;'></span>");
                    context.$docBody.append("<div data-guid='" + context.guid + "' class='wysiwygResize-selector-inner' style='border:solid 2px red;width:6px;height:6px;cursor:se-resize;position:absolute;z-index:1001;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop + elementHeight) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft + elementWidth) + "px;'></div>");

                    context.$docBody.append("<span data-guid='" + context.guid + "' class='wysiwygResize-region wysiwygResize-region-top-right' style='position:absolute;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft) + "px;border:dashed 1px grey;background-color: #EBEBE9;width:" + elementWidth + "px;height:0px;/*z-index:1000;*/'></span>");
                    context.$docBody.append("<span data-guid='" + context.guid + "' class='wysiwygResize-region wysiwygResize-region-top-down' style='position:absolute;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft) + "px;border:dashed 1px grey;background-color: #EBEBE9;width:0px;height:" + elementHeight + "px;/*z-index:1000;*/'></span>");

                    context.$docBody.append("<span data-guid='" + context.guid + "' class='wysiwygResize-region wysiwygResize-region-right-down' style='position:absolute;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft + elementWidth) + "px;border:dashed 1px grey;background-color: #EBEBE9;width:0px;height:" + elementHeight + "px;/*z-index:1000;*/'></span>");
                    context.$docBody.append("<span data-guid='" + context.guid + "' class='wysiwygResize-region wysiwygResize-region-down-left' style='position:absolute;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop + elementHeight) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft) + "px;border:dashed 1px grey;background-color: #EBEBE9;width:" + elementWidth + "px;height:0px;/*z-index:1000;*/'></span>");


                    var dragStop = function () {
                        if (elementResizeinProgress) {
                            $(currentElement)
                                .css("width", $(".wysiwygResize-region-top-right").filter(methods.guidFilter(context)).width() + "px")
                                .css('height', $(".wysiwygResize-region-top-down").filter(methods.guidFilter(context)).height() + "px");
                            methods.refresh(context);
                            var ce = currentElement;
                            context.$ifrm.trigger('webkitresize-updatecrc', [methods.crc(context.$ifrmBody.html())]);
                            elementResizeinProgress = false;
                            methods.reset(context);
                            methods.elementClick(context, ce);
                            $(".wysiwygResize-selector").filter(methods.guidFilter(context)).hide();
                            if (settings.afterResize) {
                                settings.afterResize(currentElement);
                            }
                        }
                    };

                    var iframeMouseMove = function (e) {
                        if (elementResizeinProgress) {
                            $(".wysiwygResize-selector").filter(methods.guidFilter(context)).show();

                            var resWidth = elementWidth;
                            var resHeight = elementHeight;

                            resHeight = e.pageY - elementPosition.top;
                            resWidth = e.pageX - elementPosition.left;

                            if (resHeight < 1) {
                                resHeight = 1;
                            }
                            if (resWidth < 1) {
                                resWidth = 1;
                            }

                            $(".wysiwygResize-selector").filter(methods.guidFilter(context)).css("top", (iframePos.top + elementPosition.top - ifrmScrollTop + resHeight - 50) + 'px').css("left", (iframePos.left + elementPosition.left - ifrmScrollLeft + resWidth - 50) + "px");
                            $(".wysiwygResize-selector-inner").filter(methods.guidFilter(context)).css("top", (iframePos.top + elementPosition.top - ifrmScrollTop + resHeight) + 'px').css("left", (iframePos.left + elementPosition.left - ifrmScrollLeft + resWidth) + "px");

                            $(".wysiwygResize-region-top-right").filter(methods.guidFilter(context)).css("width", resWidth + "px");
                            $(".wysiwygResize-region-top-down").filter(methods.guidFilter(context)).css("height", resHeight + "px");

                            $(".wysiwygResize-region-right-down").filter(methods.guidFilter(context)).css("left", (iframePos.left + elementPosition.left - ifrmScrollLeft + resWidth) + "px").css("height", resHeight + "px");
                            $(".wysiwygResize-region-down-left").filter(methods.guidFilter(context)).css("top", (iframePos.top + elementPosition.top - ifrmScrollTop + resHeight) + "px").css("width", resWidth + "px");
                        }

                        return false;
                    };


                    var windowMouseMove = function (e) {
                        if (elementResizeinProgress) {
                            $(".wysiwygResize-selector").filter(methods.guidFilter(context)).show();

                            var resWidth = elementWidth;
                            var resHeight = elementHeight;

                            resHeight = e.pageY - (iframePos.top + elementPosition.top - ifrmScrollTop);
                            resWidth = e.pageX - (iframePos.left + elementPosition.left - ifrmScrollLeft);

                            if (resHeight < 1) {
                                resHeight = 1;
                            }
                            if (resWidth < 1) {
                                resWidth = 1;
                            }

                            $(".wysiwygResize-selector").filter(methods.guidFilter(context)).css("top", (iframePos.top + elementPosition.top - ifrmScrollTop + resHeight - 100) + 'px').css("left", (iframePos.left + elementPosition.left - ifrmScrollLeft + resWidth - 100) + "px");
                            $(".wysiwygResize-selector-inner").filter(methods.guidFilter(context)).css("top", (iframePos.top + elementPosition.top - ifrmScrollTop + resHeight) + 'px').css("left", (iframePos.left + elementPosition.left - ifrmScrollLeft + resWidth) + "px");

                            $(".wysiwygResize-region-top-right").filter(methods.guidFilter(context)).css("width", resWidth + "px");
                            $(".wysiwygResize-region-top-down").filter(methods.guidFilter(context)).css("height", resHeight + "px");

                            $(".wysiwygResize-region-right-down").filter(methods.guidFilter(context)).css("left", (iframePos.left + elementPosition.left - ifrmScrollLeft + resWidth) + "px").css("height", resHeight + "px");
                            $(".wysiwygResize-region-down-left").filter(methods.guidFilter(context)).css("top", (iframePos.top + elementPosition.top - ifrmScrollTop + resHeight) + "px").css("width", resWidth + "px");
                        }

                        return false;
                    };

                    $(".wysiwygResize-selector, .wysiwygResize-selector-inner").filter(methods.guidFilter(context)).mousedown(function (e) {
                        if (settings.beforeResizeStart) {
                            settings.beforeResizeStart(currentElement);
                        }
                        elementResizeinProgress = true;
                        return false;
                    });

                    $(context.ifrm.contentWindow.document).mouseup(function () {
                        if (elementResizeinProgress) {
                            dragStop();
                        }
                    });

                    $(window.document).mouseup(function () {
                        if (elementResizeinProgress) {
                            dragStop();
                        }
                    });

                    $(context.ifrm.contentWindow.document).mousemove(function (e) {
                        iframeMouseMove(e);
                    });

                    $(".wysiwygResize-selector, .wysiwygResize-selector-inner").filter(methods.guidFilter(context)).mousemove(function (e) {
                        windowMouseMove(e);
                    });

                    $(window.document).mousemove(function (e) {
                        windowMouseMove(e);
                    });

                    if (settings.afterElementSelect) {
                        settings.afterElementSelect(currentElement);
                    }
                },

                rebind: function (context) {
                    context.$ifrm.contents().find(settings.selector).each(function (i, v) {
                        $(v).unbind('click');
                        $(v).click(function (e) {
                            if (e.target == v) {
                                methods.elementClick(context, v);
                            }
                        });
                    });
                },

                refresh: function (context) {
                    methods.rebind(context);

                    methods.removeResizeElements(context);

                    if (!currentElement) {
                        if (settings.afterRefresh) {
                            settings.afterRefresh(null);
                        }
                        return;
                    }

                    var element = currentElement;

                    var elementHeight = $(element).outerHeight();
                    var elementWidth = $(element).outerWidth();
                    var iframePos = context.$ifrm.offset();
                    var elementPosition = $(element).offset();
                    var ifrmScrollTop = context.$ifrmBody.scrollTop();
                    var ifrmScrollLeft = context.$ifrmBody.scrollLeft();

                    if($.browser.msie){
                        ifrmScrollTop = $(context.ifrm.contentWindow.document).scrollTop();
                        ifrmScrollLeft = $(context.ifrm.contentWindow.document).scrollLeft();
                    }

                    context.$docBody.append("<span data-guid='" + context.guid + "' class='wysiwygResize-selector' style='display:none;position:absolute;background-color:white;opacity:0;filter:alpha(opacity=0);top:" + (iframePos.top + elementPosition.top - ifrmScrollTop + elementHeight - 100) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft + elementWidth - 100) + "px;width:6px;height:6px;cursor:se-resize;/*z-index:1000;*/padding:100px;'></span>");
                    context.$docBody.append("<div data-guid='" + context.guid + "' class='wysiwygResize-selector-inner' style='border:solid 2px red;width:6px;height:6px;cursor:se-resize;position:absolute;z-index:1001;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop + elementHeight - 50) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft + elementWidth - 50) + "px;'></div>");

                    context.$docBody.append("<span data-guid='" + context.guid + "' class='wysiwygResize-region' style='position:absolute;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft) + "px;border:dashed 1px grey;background-color: #EBEBE9;width:" + elementWidth + "px;height:0px;'></span>");
                    context.$docBody.append("<span data-guid='" + context.guid + "' class='wysiwygResize-region' style='position:absolute;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft) + "px;border:dashed 1px grey;background-color: #EBEBE9;width:0px;height:" + elementHeight + "px;'></span>");

                    context.$docBody.append("<span data-guid='" + context.guid + "' class='wysiwygResize-region' style='position:absolute;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft + elementWidth) + "px;border:dashed 1px grey;background-color: #EBEBE9;width:0px;height:" + elementHeight + "px;'></span>");
                    context.$docBody.append("<span data-guid='" + context.guid + "' class='wysiwygResize-region' style='position:absolute;top:" + (iframePos.top + elementPosition.top - ifrmScrollTop + elementHeight) + "px;left:" + (iframePos.left + elementPosition.left - ifrmScrollLeft) + "px;border:dashed 1px grey;background-color: #EBEBE9;width:" + elementWidth + "px;height:0px;'></span>");

                    lastCrc = methods.crc(context.$ifrmBody.html());

                    if (settings.afterRefresh) {
                        settings.afterRefresh(currentElement);
                    }
                },

                reset: function (context) {
                    if (currentElement != null) {
                        currentElement = null;
                        elementResizeinProgress = false;
                        methods.removeResizeElements(context);

                        if (settings.afterReset) {
                            settings.afterReset();
                        }
                    }

                    methods.rebind(context);
                },

                crc: function (str) {
                    var hash = 0;
                    if (str.length == 0) return hash;
                    for (i = 0; i < str.length; i++) {
                        char = str.charCodeAt(i);
                        hash = ((hash << 5) - hash) + char;
                        hash = hash & hash;
                    }
                    return hash;
                }
            };

            var ifrm = this;
            var $ifrm = $(this);
            var $docBody = $("body");
            var $ifrmBody = $ifrm.contents().find("body");

            lastCrc = methods.crc($ifrmBody.html());

            if (!$ifrm.is('iframe')) {
                return;
            }

            var context = {
                ifrm: ifrm,
                $ifrm: $ifrm,
                $docBody: $docBody,
                $ifrmBody: $ifrmBody,
                guid: methods.guidGenerator()
            };

            if (ifrm.contentWindow.addEventListener) {
                ifrm.contentWindow.addEventListener('scroll', function () {
                    methods.reset(context);
                }, false);
            }
            else if (ifrm.contentWindow.attachEvent) {
                ifrm.contentWindow.attachEvent('onscroll', function () {
                    methods.reset(context);
                });
            }

            $(ifrm.contentWindow.document).mouseup(function (e) {
                if (!elementResizeinProgress) {
                    var x = (e.x) ? e.x : e.clientX;
                    var y = (e.y) ? e.y : e.clientY;
                    var mouseUpElement = ifrm.contentWindow.document.elementFromPoint(x, y);
                    if (mouseUpElement) {
                        var matchingElement;
                        var $select = context.$ifrm.contents().find(settings.selector);
                        var $parentsSelect = $(mouseUpElement).parents();
                        for (var psi = 0; psi < $parentsSelect.length; psi++) {
                            for (var i = 0; i < $select.length; i++) {
                                if ($select[i] == $parentsSelect[psi]) {
                                    matchingElement = $select[i];
                                    break;
                                }
                            }
                            if (matchingElement) {
                                break;
                            }
                        }
                        if (!matchingElement) {
                            methods.reset(context);
                        }
                        else {
                            methods.elementClick(context, matchingElement);
                        }
                    }
                }
            });

            $(document).keyup(function (e) {
                if (e.keyCode == 27) {
                    methods.reset(context);
                }
            });

            if (!ifrm.crcChecker) {
                ifrm.crcChecker = setInterval(function () {
                    var currentCrc = methods.crc($ifrmBody.html());
                    if (lastCrc != currentCrc) {
                        $ifrm.trigger('webkitresize-crcchanged', [currentCrc]);
                    }
                }, 1000);
            }

            $(window).resize(function () {
                methods.reset(context);
            });

            $ifrm.bind('webkitresize-crcchanged', function (event, crc) {
                if(lastCrc != crc){
                    lastCrc = crc;
                    var te = currentElement;
                    methods.reset(context);
                    if(te){
                        methods.elementClick(context, te);
                    }
                }
            });

            $ifrm.bind('webkitresize-forced-refresh', function (event, crc) {
                var te = currentElement;
                methods.reset(context);
                if(te){
                    methods.elementClick(context, te);
                }
            });

            $ifrm.bind('webkitresize-updatecrc', function (event, crc) {
                lastCrc = crc;
            });

            $ifrm.bind('webkitresize-element-selected', function (event, elem) {
                methods.reset(context);
                methods.elementClick(context, elem);
            });

            $ifrm.bind('webkitresize-reset', function (event) {
                methods.reset(context);
            });

            methods.refresh(context);

        });
    };
})(jQuery);
