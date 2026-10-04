# BÀI TẬP LỚN - ĐỀ 5: HỆ THỐNG QUẢN LÝ KHO HÀNG (INVENTORY SYSTEM)
**Mã Sinh Viên:** DTC245190012  
**Học phần:** Điện toán đám mây & DevOps / Quản trị hệ thống  
**Repository:** [https://github.com/huyvi-code/dtc245190012_InventorySystem.git](https://github.com/huyvi-code/dtc245190012_InventorySystem.git)

---

## 1. GIỚI THIỆU TỔNG QUAN

Hệ thống Quản lý Kho hàng (**Inventory Management System**) là giải pháp phần mềm toàn diện hỗ trợ doanh nghiệp quản lý hàng hóa, xuất/nhập kho, định mức tồn kho và cảnh báo thiếu hụt hàng hóa theo thời gian thực.

Dự án được triển khai trên nền tảng **Docker & Docker Compose** theo tiêu chuẩn **DevOps & Production-Ready Hardening**, tích hợp:
- **Ứng dụng Web PHP 8.2 & MariaDB/MySQL:** Thực hiện đầy đủ nghiệp vụ CRUD hàng hóa, danh mục, giao dịch nhập/xuất kho tự động tính toán số dư tồn kho với giao diện hiện đại (Bootstrap 5, FontAwesome 6, Chart.js).
- **phpMyAdmin:** Công cụ trực quan quản trị cơ sở dữ liệu.
- **Nginx Reverse Proxy:** Đóng vai trò cổng vào duy nhất (Entrypoint), ép buộc mã hóa **HTTPS với chứng chỉ SSL tự ký OpenSSL**, áp dụng đầy đủ các **Security Headers** chống tấn công web.
- **Hệ thống Giám sát Toàn diện (Prometheus + cAdvisor + Grafana):** Thu thập metrics phần cứng, container và ứng dụng, hiển thị biểu đồ trực quan qua Dashboard đã được cấu hình sẵn.
- **Hệ thống Quản lý Nhật ký Tập trung (Loki + Promtail):** Thu thập và phân tích toàn bộ log từ Nginx, Docker containers và PHP app theo thời gian thực.

---

## 2. KIẾN TRÚC HỆ THỐNG & SƠ ĐỒ MẠNG (ARCHITECTURE)

```
                            [ USER / TRÌNH DUYỆT ]
                                      │
                         HTTPS: 443   │   HTTP: 80 (Redirect 301)
                                      ▼
                      ┌───────────────────────────────┐
                      │      Nginx Reverse Proxy      │
                      │  (SSL/TLS + Security Headers) │
                      └──────────────┬────────────────┘
                                     │
      ┌──────────────────────────────┼──────────────────────────────┐
      │ (frontend_net)               │ (frontend_net)               │ (monitoring_net)
      ▼                              ▼                              ▼
┌──────────────┐              ┌──────────────┐              ┌──────────────┐
│  PHP Web App │              │  phpMyAdmin  │              │   Grafana    │
│  (PHP-FPM)   │              │ (DB Manager) │              │ (Dashboard)  │
└──────┬───────┘              └──────┬───────┘              └──────┬───────┘
       │                             │                             │
       │ (backend_net - INTERNAL)    │ (backend_net)               │
       └──────────────┬──────────────┘                             │
                      ▼                                            │
               ┌──────────────┐                                    │
               │   MariaDB/   │                                    │
               │    MySQL     │                                    │
               └──────────────┘                                    │
                                                                   │
 ┌─────────────────────────────────────────────────────────────────┴──┐
 │                       HỆ THỐNG GIÁM SÁT & LOGS                     │
 │                           (monitoring_net)                         │
 │                                                                    │
 │  ┌──────────────┐     ┌──────────────┐      ┌──────────────┐       │
 │  │   cAdvisor   │────▶│  Prometheus  │─────▶│   Grafana    │       │
 │  │(Host Metrics)│     │(Metrics DB)  │      │(Visualizer)  │       │
 │  └──────────────┘     └──────────────┘      └──────▲───────┘       │
 │                                                    │               │
 │  ┌──────────────┐                           ┌──────┴───────┐       │
 │  │   Promtail   │──────────────────────────▶│     Loki     │       │
 │  │ (Log Shipper)│                           │(Log Storage) │       │
 │  └──────────────┘                           └──────────────┘       │
 └────────────────────────────────────────────────────────────────────┘
```

---

## 3. DANH SÁCH CÁC CỔNG DỊCH VỤ (PORT MAPPING)

| STT | Tên Dịch Vụ | Container Name | Cổng Host | Cổng Container | Giao thức | Mô tả / URL truy cập |
|:---:|:---|:---|:---:|:---:|:---:|:---|
| **1** | **Nginx HTTPS** | `inventory_nginx` | **443** | 443 | HTTPS | **Cổng chính ứng dụng Kho hàng**: `https://localhost/` |
| **2** | **Nginx HTTP** | `inventory_nginx` | **80** | 80 | HTTP | Tự động chuyển hướng (Redirect 301) sang HTTPS |
| **3** | **phpMyAdmin** | `inventory_phpmyadmin` | **8080** | 80 | HTTP | Giao diện quản trị CSDL: `http://localhost:8080` hoặc `https://localhost/phpmyadmin/` |
| **4** | **Grafana** | `inventory_grafana` | **3000** | 3000 | HTTP | Giao diện Dashboard giám sát & Log Explorer: `http://localhost:3000` |
| **5** | **Prometheus** | `inventory_prometheus` | **9090** | 9090 | HTTP | Máy chủ lưu trữ Time-Series Metrics: `http://localhost:9090` |
| **6** | **cAdvisor** | `inventory_cadvisor` | **8081** | 8080 | HTTP | Thu thập số liệu tài nguyên Docker containers: `http://localhost:8081` |
| **7** | **Grafana Loki** | `inventory_loki` | **3100** | 3100 | HTTP | Endpoint nhận và truy vấn logs tập trung: `http://localhost:3100` |
| **8** | **Promtail** | `inventory_promtail` | **9080** | 9080 | HTTP | Promtail Web Metrics & Status: `http://localhost:9080` |
| **9** | **MariaDB / MySQL** | `inventory_db` | *None* | 3306 | TCP | **Hardening:** Cổng 3306 **không public ra ngoài host**, chỉ giao tiếp nội bộ trong mạng `backend` |

---

## 4. TÀI KHOẢN TRUY CẬP MẶC ĐỊNH (DEFAULT CREDENTIALS)

| Dịch vụ | Tài khoản / Tên đăng nhập | Mật khẩu | Quyền hạn / Ghi chú |
|:---|:---|:---|:---|
| **Inventory System App** | `admin` | `Admin@123456` | Quản trị viên hệ thống kho |
| **Inventory System App** | `staff` | `Admin@123456` | Nhân viên thủ kho |
| **phpMyAdmin / MySQL** | `inventory_user` | `InventorySecurePass2024!` | Người dùng CSDL ứng dụng |
| **MySQL Root** | `root` | `SuperRootSecurePass2024!` | Quản trị tối cao CSDL |
| **Grafana Dashboard** | `admin` | `GrafanaSecurePass2024!` | Quản trị viên Dashboard giám sát |

---

## 5. CÁC BIỆN PHÁP BẢO MẬT & HARDENING ĐÃ ÁP DỤNG

1. **Phân tách mạng (Network Isolation):**
   - Hệ thống chia thành 3 mạng riêng biệt: `frontend`, `backend`, `monitoring`.
   - Mạng `backend` được cấu hình `internal: true`. Container cơ sở dữ liệu `db` không có cổng public ra môi trường ngoài Internet/Host, bảo vệ triệt để dữ liệu kho hàng trước nguy cơ quét cổng (port scanning).
2. **Chứng chỉ SSL/TLS & Cấu hình HTTPS:**
   - Sử dụng OpenSSL tạo cặp khóa RSA 2048-bit với SAN (Subject Alternative Names) cho `localhost`, `127.0.0.1`, `inventory.local`.
   - Chỉ cho phép giao thức bảo mật cao **TLSv1.2** và **TLSv1.3**, loại bỏ hoàn toàn các cipher yếu.
3. **Bộ tiêu đề bảo mật HTTP (Security Headers Hardening):**
   - `Strict-Transport-Security (HSTS)`: Ép trình duyệt luôn kết nối HTTPS trong 1 năm (`max-age=31536000`).
   - `X-Frame-Options: SAMEORIGIN`: Chống tấn công Clickjacking.
   - `X-Content-Type-Options: nosniff`: Ngăn chặn MIME-sniffing.
   - `X-XSS-Protection: 1; mode=block`: Bật bộ lọc chống Cross-Site Scripting.
   - `Referrer-Policy: strict-origin-when-cross-origin`: Bảo vệ rò rỉ URL nhạy cảm.
   - `Content-Security-Policy (CSP)`: Giới hạn nguồn nạp script/style chỉ từ nguồn tin cậy (`self`, CDN).
   - `Permissions-Policy`: Vô hiệu hóa quyền truy cập phần cứng nhạy cảm (camera, microphone, geolocation).
4. **Phân quyền người dùng tối thiểu (Non-root Execution):**
   - Container PHP-FPM thực thi dưới quyền user `www-data` (UID 82 / 1000).
   - Prometheus chạy dưới user `nobody`.
   - Grafana chạy dưới UID `472:0`.
   - Loki chạy dưới UID `10001:10001`.
   - Ẩn thông tin phiên bản phần mềm (`server_tokens off` trên Nginx, `expose_php = Off` trên PHP).
   - Sử dụng `security_opt: [no-new-privileges:true]` ngăn leo thang đặc quyền container.

---

## 6. HƯỚNG DẪN CÀI ĐẶT & CHẠY HỆ THỐNG

### 6.1. Yêu cầu môi trường
- Hệ điều hành: Windows 10/11 (hỗ trợ WSL2), Linux hoặc macOS.
- Đã cài đặt **Docker Desktop** hoặc **Docker Engine** (phiên bản 20.10 trở lên) & **Docker Compose v2**.
- Công cụ dòng lệnh: Git, OpenSSL (tùy chọn nếu muốn sinh lại chứng chỉ).

### 6.2. Các bước triển khai

**Bước 1: Clone kho mã nguồn về máy**
```bash
git clone https://github.com/huyvi-code/dtc245190012_InventorySystem.git
cd dtc245190012_InventorySystem
```

**Bước 2: Khởi tạo file biến môi trường**
```bash
cp .env.example .env
```
*(Trên Windows PowerShell: `Copy-Item .env.example .env`)*

**Bước 3: Khởi chạy toàn bộ hệ thống bằng Docker Compose**
```bash
docker compose up -d --build
```

**Bước 4: Kiểm tra trạng thái hoạt động của các container**
```bash
docker compose ps
```
Tất cả các dịch vụ phải ở trạng thái `running` hoặc `healthy`:
- `inventory_nginx`
- `inventory_app`
- `inventory_db`
- `inventory_phpmyadmin`
- `inventory_cadvisor`
- `inventory_prometheus`
- `inventory_grafana`
- `inventory_loki`
- `inventory_promtail`

---

## 7. KIỂM THỬ & NGHIỆM THU HỆ THỐNG (VERIFICATION)

### 7.1. Kiểm tra HTTPS & Security Headers
Chạy lệnh curl kiểm tra phản hồi từ Nginx:
```bash
curl -k -I https://localhost
```
**Kết quả mong đợi:**
- Mã trạng thái `HTTP/2 200`
- Xuất hiện đầy đủ các Header:
  - `strict-transport-security: max-age=31536000; includeSubDomains; preload`
  - `x-frame-options: SAMEORIGIN`
  - `x-content-type-options: nosniff`
  - `x-xss-protection: 1; mode=block`
  - `content-security-policy: ...`

### 7.2. Kiểm tra Healthcheck Endpoint
Truy cập: `https://localhost/health.php`
Kết quả trả về định dạng JSON:
```json
{
    "service": "Inventory System (DTC245190012)",
    "status": "UP",
    "timestamp": "2026-10-04T10:00:00+07:00",
    "php_version": "8.2.x",
    "database": {
        "status": "CONNECTED",
        "latency_ms": 1.2
    }
}
```

### 7.3. Kiểm tra Giám sát trên Grafana
1. Mở trình duyệt truy cập: `http://localhost:3000` (hoặc `https://localhost/grafana/`)
2. Đăng nhập: `admin` / `GrafanaSecurePass2024!`
3. Vào mục **Dashboards** &rarr; Chọn **Inventory System** &rarr; **Inventory System - Container Monitoring Dashboard**.
4. Các biểu đồ CPU, RAM, Network của container hiển thị dữ liệu thời gian thực được thu thập từ Prometheus và cAdvisor.

### 7.4. Kiểm tra Quản lý Log trên Loki & Promtail
1. Trên thanh menu Grafana &rarr; Chọn **Explore**.
2. Chọn Data source: **Loki**.
3. Nhập truy vấn LogQL:
   ```logql
   {job="nginx"}
   ```
   hoặc
   ```logql
   {job="docker"}
   ```
4. Bấm **Run query** để xem toàn bộ dòng log truy cập web và log container được đẩy liên tục.

---

## 8. CẤU TRÚC THƯ MỤC DỰ ÁN

```
dtc245190012_InventorySystem/
├── .env.example                               # File mẫu cấu hình biến môi trường và mật khẩu
├── .gitignore                                 # Loại trừ dữ liệu database và file nhạy cảm
├── docker-compose.yml                         # Cấu hình ghép nối toàn bộ 9 dịch vụ & Hardening
├── Dockerfile                                 # Dockerfile build PHP 8.2 FPM (Non-root user)
├── README.md                                  # Hướng dẫn chi tiết triển khai & mô tả cổng dịch vụ
├── mysql/
│   └── init.sql                               # Script khởi tạo Database, bảng và dữ liệu mẫu
├── nginx/
│   ├── certs/
│   │   ├── generate-certs.bat                 # Script tạo chứng chỉ trên Windows
│   │   ├── generate-certs.sh                  # Script tạo chứng chỉ trên Linux/Mac
│   │   ├── openssl.cnf                        # Cấu hình OpenSSL với SAN (localhost, 127.0.0.1)
│   │   ├── server.crt                         # Chứng chỉ SSL tự ký
│   │   └── server.key                         # Khóa riêng tư SSL
│   ├── conf.d/
│   │   └── default.conf                       # Cấu hình VirtualHost HTTPS, Reverse Proxy & Headers
│   └── nginx.conf                             # Cấu hình Nginx Core tối ưu hiệu năng
├── prometheus/
│   └── prometheus.yml                         # Cấu hình Scrape metrics Prometheus, cAdvisor, Nginx
├── grafana/
│   └── provisioning/
│       ├── dashboards/
│       │   ├── dashboards.yml                 # Khai báo Provider tự động nạp Dashboards
│       │   └── inventory-system-dashboard.json# Dashboard giám sát tài nguyên Container & Services
│       └── datasources/
│           └── datasources.yml                # Khai báo Data sources tự động (Prometheus + Loki)
├── loki/
│   └── loki-config.yml                        # Cấu hình lưu trữ và index nhật ký tập trung Loki
├── promtail/
│   └── promtail-config.yml                    # Cấu hình thu thập log Docker, Nginx gửi về Loki
└── src/                                       # Mã nguồn ứng dụng Web PHP Quản lý Kho hàng
    ├── config.php                             # Kết nối cơ sở dữ liệu PDO & hàm tiện ích
    ├── index.php                              # Bảng điều khiển KPI, biểu đồ tồn kho & cảnh báo
    ├── products.php                           # Danh sách sản phẩm, lọc theo tồn kho & tìm kiếm
    ├── product_add.php                        # Thêm mới sản phẩm
    ├── product_edit.php                       # Sửa thông tin hàng hóa
    ├── product_delete.php                     # Xóa sản phẩm an toàn
    ├── categories.php                         # Quản lý nhóm danh mục hàng
    ├── transactions.php                       # Quản lý lập phiếu Nhập kho / Xuất kho
    ├── health.php                             # API kiểm tra tình trạng kết nối CSDL và PHP
    ├── assets/
    │   ├── css/style.css                      # Giao diện tùy biến
    │   └── js/main.js                         # JavaScript tương tác frontend
    └── includes/
        ├── header.php                         # Navbar, thanh điều hướng & Bootstrap 5
        └── footer.php                         # Footer & thư viện JavaScript
```

---

## 9. LỊCH SỬ COMMIT THEO YÊU CẦU

Dự án được phân chia thành đúng 3 mốc commit theo yêu cầu bài tập:
1. **Commit 1:** `Nginx reverse proxy (HTTPS & Security headers)`  
   - Mã nguồn ứng dụng Web PHP, CSDL mẫu, Dockerfile, Nginx Reverse Proxy, cấu hình SSL/TLS tự ký và Security Headers.
2. **Commit 2:** `Prometheus + Grafana monitoring stack`  
   - Cấu hình Prometheus, cAdvisor, Grafana datasources, Dashboard tự động và cập nhật docker-compose.yml.
3. **Commit 3:** `Loki + Promtail logging stack + README`  
   - Cấu hình Loki, Promtail, hoàn thiện đầy đủ tài liệu README.md và đẩy mã nguồn lên GitHub.
