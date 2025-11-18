# LOGIC TÍNH TOÁN KHUNG TRANH MỚI

## Tổng quan
Hệ thống đã được cập nhật để tính toán khung tranh theo **chu vi thực tế** và **khấu trừ góc xéo**, thay vì nhập thủ công số cây và chiều dài.

---

## CÁCH TÍNH CŨ (Đã loại bỏ)

### Input:
- Số lượng cây: VD 4 cây
- Chiều dài mỗi cây: VD 240 cm/cây

### Output:
- Tổng chiều dài = 4 × 240 = 960 cm

### Vấn đề:
- Không tính đến chu vi thực tế của khung
- Không tính đến góc xéo khi ghép khung
- Người dùng phải tự tính toán

---

## CÁCH TÍNH MỚI (Hiện tại)

### Input từ người dùng:
1. **Kích thước khung:**
   - Chiều dài khung: VD 60 cm
   - Chiều rộng khung: VD 40 cm

2. **Khấu trừ góc xéo:**
   - Người dùng tự nhập tổng chiều dài 4 góc xéo: VD 17 cm
   - Đây là phần thừa khi cắt góc 45° (4 góc đỏ trong hình)

3. **Thông tin cây gỗ:**
   - Chọn loại cây từ kho

### Công thức tính toán:

#### 1. Chu vi khung
```
Chu vi = 2 × (Chiều dài + Chiều rộng)
```
**Ví dụ:** Khung 60×40 cm
```
Chu vi = 2 × (60 + 40) = 200 cm
```

#### 2. Khấu trừ góc xéo
Khi làm khung tranh, 4 góc phải cắt xéo 45° để ghép lại. Mỗi góc xéo "ăn mất" một đoạn cây.

**Người dùng tự nhập** tổng chiều dài 4 góc xéo cần khấu trừ.

**Ví dụ:** Người dùng nhập 17 cm
```
Khấu trừ góc = 17 cm (do người dùng nhập)
```

**Lưu ý:** Nếu muốn tính toán, có thể dùng công thức:
```
Khấu trừ góc ≈ 4 × Chiều rộng cây × 1.414
```
Nhưng trong hệ thống, người dùng tự nhập giá trị này.

#### 3. Tổng chiều dài cây cần
```
Tổng cây cần = Chu vi + Khấu trừ góc
```

**Ví dụ:**
```
Tổng cây cần = 200 + 16.97 = 216.97 cm
```

#### 4. Tính số cây cần dùng
Hệ thống tự động tính số cây cần dựa trên chiều dài mỗi cây trong kho:

```
Số cây cần = CEIL(Tổng cây cần / Chiều dài mỗi cây trong kho)
```

**Ví dụ:** Cây trong kho dài 300 cm
```
Số cây cần = CEIL(216.97 / 300) = 1 cây
Chiều dài cắt mỗi cây = 216.97 / 1 = 216.97 cm
Phần dư = 300 - 216.97 = 83.03 cm
```

---

## VÍ DỤ CỤ THỂ

### Tình huống: Làm khung 60×40 cm, người dùng nhập khấu trừ góc 17 cm

#### Bước 1: Tính chu vi
```
Chu vi = 2 × (60 + 40) = 200 cm
```

#### Bước 2: Nhập khấu trừ góc
```
Khấu trừ góc = 17 cm (người dùng nhập)
```

#### Bước 3: Tổng cây cần
```
Tổng = 200 + 17 = 217 cm
```

#### Bước 4: Kiểm tra kho
Giả sử kho có:
- Cây A: 300 cm/cây, còn 5 cây

#### Bước 5: Tính số cây cần
```
Số cây cần = CEIL(216.97 / 300) = 1 cây
Chiều dài cắt = 216.97 cm
Phần dư = 300 - 216.97 = 83.03 cm
```

#### Kết quả:
- Trừ 1 cây từ kho (còn 4 cây)
- Tạo phần dư: 1 cây × 83 cm

---

## TRƯỜNG HỢP NHIỀU LOẠI CÂY

Nếu sử dụng nhiều loại cây (VD: cây A cho 2 cạnh dài, cây B cho 2 cạnh ngắn):

### Công thức:
```
Tổng cây cần được chia đều cho các loại cây
```

**Ví dụ:** Khung 60×40 cm, khấu trừ góc 17 cm, dùng 2 loại cây

#### Tính toán:
```
Chu vi = 200 cm
Khấu trừ góc = 17 cm (người dùng nhập)
Tổng cây cần = 200 + 17 = 217 cm
```

#### Loại cây 1:
```
Cây cần = 217 / 2 = 108.5 cm
```

#### Loại cây 2:
```
Cây cần = 217 / 2 = 108.5 cm
```

---

## LỢI ÍCH CỦA LOGIC MỚI

1. **Chính xác hơn:** Tính toán dựa trên kích thước thực tế của khung
2. **Tự động hóa:** Hệ thống tự tính số cây cần, không cần nhập thủ công
3. **Linh hoạt:** Người dùng tự nhập khấu trừ góc xéo dựa trên thực tế
4. **Quản lý kho tốt hơn:** Tự động tạo phần dư khi cắt cây
5. **Dễ sử dụng:** Chỉ cần nhập kích thước khung, khấu trừ góc và chọn loại cây

---

## CẤU TRÚC DATABASE MỚI

### Bảng `frames`:
- `frame_length`: Chiều dài khung (cm)
- `frame_width`: Chiều rộng khung (cm)
- `perimeter`: Chu vi khung (cm)
- `corner_deduction`: Tổng khấu trừ góc xéo (cm)
- `total_wood_needed`: Tổng chiều dài cây cần (cm)

### Bảng `frame_items`:
- `wood_width`: Chiều rộng cây gỗ (cm) - không bắt buộc, chỉ để tham khảo
- `tree_quantity`: Số cây cần dùng (tự động tính)
- `length_per_tree`: Chiều dài cắt mỗi cây (tự động tính)
- `total_length`: Tổng chiều dài (tự động tính)

---

## GHI CHÚ

- **Khấu trừ góc xéo** là tổng chiều dài 4 góc thừa khi cắt góc 45° (4 góc đỏ trong hình)
- Người dùng tự đo và nhập giá trị này dựa trên thực tế
- Nếu muốn ước tính, có thể dùng công thức: `4 × Chiều rộng cây × 1.414`
- Công thức này áp dụng cho khung chữ nhật/vuông tiêu chuẩn
- Nếu khung có hình dạng đặc biệt, cần điều chỉnh công thức
