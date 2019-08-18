<?php
defined('BASEPATH') OR exit('No direct script access allowed');

?>
<div class="frm-wrapper page-wrapper">
    <h1>Thêm trang mới</h1>
    <?php echo form_open_multipart(uri_string() . '?_action=add', ['data-abide novalidate class' => 'frm-page', 'id' => 'frm-page']); ?>
    <ul id="frm-page-tabs" class="tabs" data-tabs>
        <li class="tabs-title is-active"><a href="#tab-content" aria-selected="true">Nội dung</a></li>
        <li class="tabs-title"><a href="#tab-meta">Meta data</a></li>
        <li class="tabs-title"><a href="#tab-css">CSS</a></li>
        <li class="tabs-title"><a href="#tab-js">Script</a></li>
    </ul>
    <div class="tabs-content" data-tabs-content="frm-page-tabs">
        <div class="tabs-panel is-active" id="tab-content">
            <div>
                <label for="title">Tiêu đề <sup>*</sup></label>
                <input value="<?php echo _post('title')?>" name="title" id="title" type="text" required pattern="^(.*\S+.*)$" maxlength="250" placeholder="Nhập tiêu đề">
            </div>
            <div>
                <label for="slug">Slug</label>
                <input value="<?php echo _post('slug')?>" name="slug" id="slug" type="text" pattern="^[a-z0-9_-]+$" maxlength="250" aria-describedby="slug_txt">
                <p class="help-text" id="slug_txt">Chỉ bao gồm các ký tự bảng chữ cái thường không dấu, chữ số, gạch dưới và gạch ngang. Nếu để trống sẽ tự động lấy theo tiêu đề.</p>
            </div>
            <div class="editor-wrapper">
                <label for="content">Nội dung</label>
                <textarea class="tinymce" name="content" id="content"><?php echo _post('content')?></textarea>
            </div>
            <div>
                <label for="short">Mô tả ngắn</label>
                <textarea rows="3" name="short" id="short" pattern="^(.*\S+.*)$" aria-describedby="short_txt"><?php echo _post('short')?></textarea>
                <p class="help-text" id="short_txt">Mô tả ngắn là một đoạn mô tả về nội dung mà bạn tự nhập bằng tay, có thể được sử dụng để hiển thị trong trang.</p>
            </div>
            <div>
                <label for="img">Ảnh đại diện</label>
                <div class="file-input-wrap thumbnail-input">
                    <div class="thumbnails">
                        <div class="trap-wrap">
                            <input name="img" id="img" type="file" accept="image/*" pattern="^.+?\.(png|PNG|jpg|JPG|jpeg|JPEG|gif|GIF)$" aria-describedby="img_txt">
                            <div class="trap-plus"><i class="fa fa-plus" aria-hidden="true"></i></div>
                        </div>
                    </div>
                </div>
                <p class="help-text" id="img_txt">Chấp nhận các file ảnh có định dạng (png, jpg, jpeg, gif)</p>
            </div>
            <div>
                <label for="pid">Trang cha</label>
                <div class="br"></div>
                <select name="pid" id="pid">
                    <option value="" selected>Không có trang cha</option>
                </select>
            </div>
            <div>
                <label for="pages_layouts_id">Giao diện</label>
                <div class="br"></div>
                <?php /** @var array $layouts */
                echo form_dropdown('pages_layouts_id', $layouts, [], 'id="pages_layouts_id"'); ?>
            </div>
            <div class="inline-block">
                <label for="pos">Thứ tự</label>
                <input type="number" name="pos" id="pos" value="<?php echo _post('pos', 0)?>">
            </div>
            <div>
                <label for="title_label">Tiêu đề thay thế</label>
                <input value="<?php echo _post('title_label')?>" name="title_label" id="title_label" type="text" pattern="^(.*\S+.*)$" maxlength="250" aria-describedby="title_label_txt">
                <p class="help-text" id="title_label_txt">This renames the page title field from "Title". This is useful if you are using "Title" as something else, like "Product Name" or "Team Member Name".</p>
            </div>
            <div class="inline-block">
                <label for="published_on">Thời điểm đăng <sup>*</sup></label>
                <input required type="text" class="fdatepicker pick_time" id="published_on" name="published_on" value="<?=date('Y-m-d H:i')?>" aria-describedby="published_on_txt">
                <p class="help-text" id="published_on_txt">Thời điểm trang bắt đầu hiển thị.</p>
            </div>
            <div>
                <label for="status">Status</label>
                <div class="br"></div>
                <select name="status" id="status">
                    <option value="draft" selected>Nháp</option>
                    <option value="live">Đăng</option>
                    <option value="hide">Ẩn</option>
                </select>
            </div>
            <div class="inline-block">
                <label for="restricted_password">Mật khẩu</label>
                <input value="<?php echo _post('restricted_password')?>" type="text" name="restricted_password" id="restricted_password" aria-describedby="restricted_password_txt">
                <p class="help-text" id="restricted_password_txt">Nhập mật khẩu để bảo vệ trang. (nếu có)</p>
            </div>
            <div>
                <label class="font-normal">
                    <input type="checkbox" name="comment_enabled" id="comment_enabled">
                    Cho phép bình luận
                </label>
            </div>
            <div>
                <label class="font-normal">
                    <input type="checkbox" checked name="rss_enabled" id="rss_enabled">
                    RSS
                </label>
            </div>
        </div>
        <div class="tabs-panel" id="tab-meta">
            <div>
                <div class="meta-label-wrap meta-title-input-wrap">
                    <label for="meta_title">Meta title <a target="_blank" title="The meta title can be used to determine the title used on search engine result pages." href="https://support.google.com/webmasters/answer/35624?hl=en#page-titles">[?]</a></label>
                    <div class="meta-char-counter">Số ký tự <span class="chars count-empty">0 - Empty</span></div>
                </div>
                <div class="meta-title-input-wrap">
                    <input value="<?php echo _post('meta_title')?>" type="text" name="meta_title" id="meta_title" pattern="^(.*\S+.*)$" maxlength="250">
                    <span id="meta-title-offset"></span>
                    <span id="meta-title-placeholder"><?php echo ' | ' . $this->setting->site_name;?></span>
                </div>
                <label class="font-normal">
                    <input type="checkbox" name="meta_append_name" aria-describedby="meta_append_name_txt">
                    Append the site-name? <cite id="meta_append_name_txt" class="inline-block">Use this when you want to rearrange the title parts manually.</cite>
                </label>
            </div>
            <div>
                <div class="meta-label-wrap meta-description-input-wrap">
                    <label for="meta_description">Meta description <a target="_blank" title="The meta description can be used to determine the text used under the title on search engine results pages." href="https://support.google.com/webmasters/answer/35624?hl=en#meta-descriptions">[?]</a></label>
                    <div class="meta-char-counter">Số ký tự <span class="chars count-empty">0 - Empty</span></div>
                </div>
                <div class="meta-description-input-wrap">
                    <textarea rows="3" name="meta_description" id="meta_description" pattern="^(.*\S+.*)$"><?php echo _post('meta_description')?></textarea>
                </div>
            </div>
            <div>
                <label>Robots Meta Settings</label>
                <div class="br"></div>
                <label class="font-normal">
                    <input type="checkbox" name="meta_noindex">
                    noindex <a target="_blank" href="https://support.google.com/webmasters/answer/93710?hl=en" title="This tells search engines not to show this page in their search results.">[?]</a>
                </label>
                <div class="br"></div>
                <label class="font-normal">
                    <input type="checkbox" name="meta_nofollow">
                    nofollow <a target="_blank" href="https://support.google.com/webmasters/answer/96569?hl=en" title="This tells search engines not to follow links on this page.">[?]</a>
                </label>
                <div class="br"></div>
                <label class="font-normal">
                    <input type="checkbox" name="meta_noarchive">
                    noarchive <a target="_blank" href="https://support.google.com/webmasters/answer/79812?hl=en" title="This tells search engines not to save a cached copy of this page.">[?]</a>
                </label>
            </div>
            <div>
                <label for="canonical_url">Canonical URL <a target="_blank" href="https://support.google.com/webmasters/answer/139066?hl=en" title="This urges search engines to go to the outputted URL.">[?]</a></label>
                <input value="<?php echo _post('canonical_url')?>" name="canonical_url" id="canonical_url" type="url">
            </div>
            <div>
                <label for="redirect_url">301 Redirect URL <a target="_blank" href="https://support.google.com/webmasters/answer/93633?hl=en" title="This will force visitors to go to another URL.">[?]</a></label>
                <input value="<?php echo _post('redirect_url')?>" name="redirect_url" id="redirect_url" type="url">
            </div>
            <div class="inline-block">
                <label for="img_social" class="block">Social Image <a target="_blank" href="https://developers.facebook.com/docs/sharing/best-practices#images" title="Set preferred page Social Image URL location.">[?]</a></label>
                <div class="file-input-wrap social-input">
                    <div class="thumbnails">
                        <div class="trap-wrap">
                            <input name="img_social" id="img_social" type="file" accept="image/*" pattern="^.+?\.(png|PNG|jpg|JPG|jpeg|JPEG|gif|GIF)$" aria-describedby="img_social_txt">
                            <div class="trap-plus"><i class="fa fa-plus" aria-hidden="true"></i></div>
                        </div>
                    </div>
                </div>
                <p class="help-text" id="img_social_txt">Chấp nhận các file ảnh có định dạng (png, jpg, jpeg, gif)</p>
            </div>
        </div>
        <div class="tabs-panel" id="tab-css">
            <div>
                <label for="css">CSS inline</label>
                <textarea name="css" id="css" class="codemirror codemirror-css"><?php echo _post('css')?></textarea>
            </div>
            <div class="inline-block">
                <label for="css_class_wrap">CSS class wrapper</label>
                <input value="<?php echo _post('css_class_wrap')?>" type="text" name="css_class_wrap" id="css_class_wrap" pattern="^(.*\S+.*)$">
            </div>
        </div>
        <div class="tabs-panel" id="tab-js">
            <div>
                <label for="js">Script inline</label>
                <textarea name="js" id="js" class="codemirror codemirror-js"><?php echo _post('js')?></textarea>
            </div>
        </div>
        <div class="lang-wrap">
            <label for="languages_id">Ngôn ngữ</label>
            <div class="select-language">
                <img class="flag" src="<?php echo lang_item(LANG)->flag;?>" alt="<?php echo escape_html(lang_item(LANG)->name);?>">
                <?php
                /** @var array $languages */
                echo form_dropdown('languages_id', $languages, [LANG], 'id="languages_id"'); ?>
            </div>
        </div>
        <div class="btn-submit">
            <?php echo form_hidden('_action', 'add'); ?>
            <button type="submit" class="button">Thêm mới</button>
        </div>
    </div>
    <?php echo form_close();?>
</div>
