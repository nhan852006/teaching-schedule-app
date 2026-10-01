#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
DocxToHtmlConverter: Chuyển đổi file giáo án Word (.docx) sang HTML chất lượng cao
- Sử dụng textutil trên macOS để trích xuất cấu trúc văn bản và bảng biểu
- Trích xuất 100% hình ảnh minh họa từ docx và nhúng dạng base64 Data URI vào đúng vị trí
- Tối ưu hóa CSS bảng biểu, viền nét, phông chữ Times New Roman chuẩn A4 hành chính
"""

import sys
import os
import zipfile
import xml.etree.ElementTree as ET
import base64
import subprocess
import re

def convert_docx_to_html(docx_path, out_html_path):
    if not os.path.exists(docx_path):
        sys.stderr.write(f"Error: File not found: {docx_path}\n")
        sys.exit(1)

    temp_raw_html = out_html_path + ".raw.html"
    try:
        # 1. Chuyển đổi DOCX sang HTML qua textutil của macOS
        res = subprocess.run(
            ['textutil', '-convert', 'html', docx_path, '-output', temp_raw_html],
            capture_output=True,
            text=True
        )
        if res.returncode != 0 or not os.path.exists(temp_raw_html):
            sys.stderr.write(f"textutil failed: {res.stderr}\n")
            sys.exit(res.returncode if res.returncode != 0 else 1)

        with open(temp_raw_html, 'r', encoding='utf-8', errors='ignore') as f:
            html = f.read()

        # 2. Trích xuất CSS gốc từ thẻ <style>
        style_match = re.search(r'<style[^>]*>(.*?)</style>', html, re.DOTALL | re.IGNORECASE)
        orig_css = style_match.group(1) if style_match else ''

        # 3. Đọc dữ liệu quan hệ hình ảnh từ DOCX
        rel_map = {}
        all_p = []
        w_ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
        a_ns = 'http://schemas.openxmlformats.org/drawingml/2006/main'
        v_ns = 'urn:schemas-microsoft-com:vml'

        try:
            with zipfile.ZipFile(docx_path) as z:
                if 'word/_rels/document.xml.rels' in z.namelist():
                    rels_root = ET.fromstring(z.read('word/_rels/document.xml.rels'))
                    for r in rels_root:
                        if 'Id' in r.attrib:
                            rel_map[r.attrib['Id']] = r.attrib.get('Target', '')

                if 'word/document.xml' in z.namelist():
                    doc_root = ET.fromstring(z.read('word/document.xml'))
                    all_p = list(doc_root.iter(f'{{{w_ns}}}p'))
        except Exception as e:
            sys.stderr.write(f"Warning: XML parsing error in docx: {e}\n")

        # 4. Trích xuất và nhúng hình ảnh vào HTML
        injected_count = 0
        if all_p and os.path.exists(docx_path):
            with zipfile.ZipFile(docx_path) as z:
                for idx, p in enumerate(all_p):
                    blips = list(p.iter(f'{{{a_ns}}}blip'))
                    vmls = list(p.iter(f'{{{v_ns}}}imagedata'))
                    targets = []
                    for b in blips:
                        rId = b.attrib.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}embed')
                        if rId and rel_map.get(rId):
                            targets.append(rel_map[rId])
                    for v in vmls:
                        rId = v.attrib.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')
                        if rId and rel_map.get(rId):
                            targets.append(rel_map[rId])

                    if not targets:
                        continue

                    # Tìm đoạn văn bản neo ngay trước hình ảnh
                    anchor_text = ''
                    for b_idx in range(idx - 1, max(-1, idx - 15), -1):
                        t = ''.join(all_p[b_idx].itertext()).strip()
                        if t:
                            anchor_text = t
                            break

                    for target in targets:
                        zip_target = 'word/' + target if not target.startswith('word/') else target
                        if zip_target not in z.namelist():
                            continue

                        img_data = z.read(zip_target)
                        ext = zip_target.split('.')[-1].lower()
                        mime = 'image/png' if ext == 'png' else ('image/jpeg' if ext in ['jpg', 'jpeg'] else ('image/gif' if ext == 'gif' else 'image/png'))
                        b64 = base64.b64encode(img_data).decode('utf-8')

                        img_tag = (
                            f'<div class="docx-embedded-img" style="text-align: center; margin: 10px 0; page-break-inside: avoid;">'
                            f'<img src="data:{mime};base64,{b64}" '
                            f'style="max-width: 95%; max-height: 520px; height: auto; border: 1px solid #333333; border-radius: 2px; display: inline-block;" />'
                            f'</div>'
                        )

                        clean_anchor = re.sub(r'^[0-9\.\s]+', '', anchor_text).strip()
                        search_key = clean_anchor[-25:] if len(clean_anchor) > 25 else clean_anchor

                        pos = html.find(search_key) if search_key else -1
                        if pos != -1:
                            p_break = re.search(r'<p[^>]*>\s*(?:<br\s*/?>|&nbsp;|\s*)\s*</p>', html[pos:])
                            if p_break:
                                s_pos = pos + p_break.start()
                                e_pos = pos + p_break.end()
                                html = html[:s_pos] + img_tag + html[e_pos:]
                                injected_count += 1
                                continue

                        # Nếu không tìm thấy vị trí neo, chèn trước thẻ đóng body
                        html = html.replace('</body>', f'{img_tag}</body>')
                        injected_count += 1

        # 5. Tách phần body
        body_match = re.search(r'<body[^>]*>(.*?)</body>', html, re.DOTALL | re.IGNORECASE)
        body_content = body_match.group(1) if body_match else html

        # 6. Định dạng CSS chuẩn văn bản sư phạm & in ấn A4
        enhanced_css = f"""
        {orig_css}

        /* Chuẩn hóa kiểu chữ và bảng biểu A4 */
        .custom-template-content {{
            font-family: 'Times New Roman', 'Tinos', serif !important;
            font-size: 13pt !important;
            line-height: 1.45 !important;
            color: #000000 !important;
            word-wrap: break-word;
        }}

        .custom-template-content table {{
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 12px 0 !important;
            font-size: 12pt !important;
            page-break-inside: auto;
        }}

        .custom-template-content tr {{
            page-break-inside: avoid;
            page-break-after: auto;
        }}

        .custom-template-content td, 
        .custom-template-content th {{
            border: 1px solid #000000 !important;
            padding: 6px 8px !important;
            vertical-align: top !important;
        }}

        .custom-template-content th {{
            font-weight: bold !important;
            text-align: center !important;
            background-color: #f8fafc;
        }}

        .custom-template-content p {{
            margin-top: 3px !important;
            margin-bottom: 3px !important;
        }}

        .custom-template-content img {{
            max-width: 95% !important;
            height: auto !important;
            page-break-inside: avoid;
        }}
        """

        full_output = f"<style>{enhanced_css}</style>\n<div class=\"custom-template-content\">{body_content}</div>"

        with open(out_html_path, 'w', encoding='utf-8') as f:
            f.write(full_output)

        print(f"OK: Converted successfully with {injected_count} images.")

    finally:
        if os.path.exists(temp_raw_html):
            try:
                os.remove(temp_raw_html)
            except:
                pass

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: docx_to_html.py <input.docx> <output.html>")
        sys.exit(1)
    convert_docx_to_html(sys.argv[1], sys.argv[2])
