import { React, useState } from "react";

class ResultData {
    error?: Error;
    received?: string;
    brandData?: BrandData;
    parsedData?: string;

    constructor(resData: any) {
        resData.error && (this.error = resData.error);
        resData.received && (this.received = resData.received);
        resData.brandData &&
            (this.brandData = new BrandData(resData.brandData));
        resData.parsedData && (this.parsedData = resData.parsedData);
    }
}

class BrandData {
    colors?: ColorData;
    fonts?: FontData;

    constructor(brandData: Record<string, any>) {
        brandData.colors && (this.colors = brandData.colors as ColorData);
        brandData.fonts && (this.fonts = brandData.fonts as FontData);
    }
    π;
}

interface ColorData {
    [color: string]: string[];
}

interface FontData {
    [font: string]: string[];
}

const Home = () => {
    const [input, setInput] = useState("");
    const [loading, setLoading] = useState(false);
    const [resData, setResData]: [ResultData | null, any] = useState(null);

    const handleSubmit = () => {
        // const fetchAddress = "/api/brand_scraper";
        const fetchAddress = "api/test";

        setLoading(true);
        fetch(fetchAddress, {
            method: "Post",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ url: input }),
        })
            .then((res) => res.json())
            .then((data) => {
                setResData(new ResultData(data) as ResultData);
                setLoading(false);
            });
    };

    return (
        <>
            <div>
                <h1>Home</h1>
            </div>
            <input
                type="text"
                onChange={(e) => setInput(e.target.value)}
            ></input>
            <button onClick={handleSubmit}>Submit</button>
            {loading && <Loading />}
            {resData ? <ResultsDisplay resData={resData} /> : null}
        </>
    );
};

const Loading = () => {
    return <div className="loadingResults">Loading</div>;
};

const ResultsDisplay = ({ resData }) => {
    return (
        <div className="resultsDisplay">
            {resData.error ? (
                <ErrorDisplay error={resData.error} />
            ) : resData.brandData ? (
                <DataDisplay resData={resData} />
            ) : (
                "No error and no data?!?"
            )}
        </div>
    );
};

const ErrorDisplay = ({ error }: { error: Error }) => {
    return <div className="errorDisplay">{error}</div>;
};

const DataDisplay = ({ resData }: { resData: ResultData }) => {
    return (
        <div className="dataDisplay">
            <h3>Brand Colors for {resData.received}</h3>
            {resData.brandData.colors ? (
                <ColorDisplay colors={resData.brandData.colors} />
            ) : null}
            {resData.brandData.fonts ? (
                <FontDisplay fonts={resData.brandData.fonts} />
            ) : null}
            <ParsedDataDisplay parsedData={resData.parsedData} />
        </div>
    );
};

const ColorDisplay = ({ colors }: { colors: ColorData }) => {
    return (
        <div className="colorDisplay">
            <h3>Colors</h3>
            <p>{JSON.stringify(colors, null, 2)}</p>
        </div>
    );
};

const FontDisplay = ({ fonts }: { fonts: FontData }) => {
    return (
        <div className="fontDisplay">
            <h3>Fonts</h3>
            <p>{JSON.stringify(fonts, null, 2)}</p>
        </div>
    );
};

const ParsedDataDisplay = ({ parsedData }: { parsedData: string }) => {
    return (
        <div className="fullDataDisplay">
            <h3>All Parsed Data</h3>
            <p>{parsedData}</p>
        </div>
    );
};

export default Home;
