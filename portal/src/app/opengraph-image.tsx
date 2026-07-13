import { ImageResponse } from "next/og";

export const size = { width: 1200, height: 630 };
export const contentType = "image/png";

export default async function Image() {
  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          flexDirection: "column",
          alignItems: "center",
          justifyContent: "center",
          background: "linear-gradient(135deg, #0f1222 0%, #1c1440 55%, #2b1a55 100%)",
          fontFamily: "sans-serif",
        }}
      >
        <div
          style={{
            display: "flex",
            alignItems: "center",
            gap: 20,
            padding: "20px 36px",
            borderRadius: 999,
            background: "rgba(255,255,255,0.08)",
            border: "1px solid rgba(255,255,255,0.15)",
          }}
        >
          <div
            style={{
              width: 56,
              height: 56,
              borderRadius: 16,
              background: "#6d5bf7",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              fontSize: 30,
              color: "white",
              fontWeight: 700,
            }}
          >
            P
          </div>
          <div style={{ fontSize: 40, color: "white", fontWeight: 700 }}>PrimeWebKit</div>
        </div>
        <div style={{ marginTop: 40, fontSize: 44, color: "white", fontWeight: 700, textAlign: "center", maxWidth: 900 }}>
          AI chatbots trained on your content
        </div>
        <div style={{ marginTop: 16, fontSize: 24, color: "rgba(255,255,255,0.7)", textAlign: "center", maxWidth: 800 }}>
          Crawl your site, upload docs, go live in minutes
        </div>
      </div>
    ),
    { ...size },
  );
}
